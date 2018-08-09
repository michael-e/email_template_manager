<?php

if (!defined('EMAILTEMPLATES')) define('EMAILTEMPLATES', WORKSPACE . '/email-templates');
if (!defined('ETMDIR')) define('ETMDIR', EXTENSIONS . '/email_template_manager');
require_once(TOOLKIT . '/class.extensionmanager.php');
require_once 'class.emailtemplate.php';

class EmailTemplateManager
{
    static $errorMsg = '';

    public static function load($handle)
    {
        $classname = self::getClassNameFromHandle($handle);
        if (self::find($handle)) {
            return new $classname;
        } else {
            return false;
        }
    }

    public static function find($handle)
    {
        $filename = self::getFileNameFromHandle($handle);
        $classname = self::getClassNameFromHandle($handle);
        if (is_dir(EMAILTEMPLATES . "/$handle")) {
            if (file_exists(EMAILTEMPLATES . "/$handle/$filename")) {
                include_once(EMAILTEMPLATES . "/$handle/$filename");
                if (class_exists($classname)) {
                    return EMAILTEMPLATES . "/$handle/$filename";
                } else {
                    self::$errorMsg = "Class $classname not set in file $filename";

                    return false;
                }
            } else {
                self::$errorMsg = "File $filename not set for template $handle";

                return false;
            }
        } else {
            $found = false;
            foreach (ExtensionManager::listInstalledHandles() as $extension) {
                if (is_dir(EXTENSIONS . '/' . $extension . '/email-templates/' . $handle)) {
                    if (file_exists(EXTENSIONS . '/' . $extension . "/email-templates/$handle/$filename")) {
                        include_once(EXTENSIONS . '/' . $extension . "/email-templates/$handle/$filename");
                        if (class_exists($classname)) {
                            $found = true;

                            return EXTENSIONS . '/' . $extension . "/email-templates/$handle";
                        } else {
                            self::$errorMsg = "Class $classname not set in file $filename";

                            return false;
                        }
                    } else {
                        self::$errorMsg = "File $filename not set for template $handle";

                        return false;
                    }
                }
            }
            if (!$found) {
                self::$errorMsg = "Template $handle not found";

                return false;
            }
        }
    }

    public static function create($config)
    {
        $handle = self::getHandleFromName($config['name']);
        if (!self::find($handle)) {
            if (!is_dir(EMAILTEMPLATES . "/$handle")) {
                mkdir(EMAILTEMPLATES . "/$handle");
                $etm = new EmailTemplateManager();
                if (!$etm->_writeConfig($handle, $etm->_parseConfigTemplate($handle, $config), true)) return false;
                if (!$etm->_writeLayout($handle, 'Plain', file_get_contents(ETMDIR . '/content/templates/xsl-plain.tpl'), true)) return false;
                if (!$etm->_writeLayout($handle, 'HTML',  file_get_contents(ETMDIR . '/content/templates/xsl-html.tpl'), true)) return false;

                Symphony::ExtensionManager()->notifyMembers(
                    'EmailTemplatePostCreate',
                    '/extension/email_template_manager/',
                    array(
                        'config' => $config
                    )
                );

                return true;
            } else {
                self::$errorMsg = 'Dir ' . EMAILTEMPLATES . "/$handle already exists.";

                return false;
            }
        } else {
            self::$errorMsg = "Template $handle already exists.";

            return false;
        }
    }

    public static function editConfig($handle, $config)
    {
        if ($template = self::load($handle)) {
            if ($template->editable === true) {
                $etm = new EmailTemplateManager();
                if ($etm->_writeConfig($handle, $etm->_parseConfigTemplate($handle, $config))) {

                    $old_dir = dirname(self::find($handle));
                    $new_dir = dirname($old_dir) . '/' . self::getHandleFromName($config['name']);

                    if (self::getHandleFromName($config['name']) !== $handle) {
                        if (!is_dir($new_dir)) {
                            if (!rename($old_dir, $new_dir)) return false;

                            Symphony::ExtensionManager()->notifyMembers(
                                'EmailTemplatePostSave',
                                '/extension/email_template_manager/',
                                array(
                                    'old_handle' => $handle,
                                    'config' => $config
                                )
                            );

                            return rename($new_dir . '/' . self::getFileNameFromHandle($handle), $new_dir . '/' . self::getFileNameFromHandle(self::getHandleFromName($config['name'])));
                        }
                    }

                    return true;
                } else {
                    return false;
                }
            } else {
                self::$errorMsg = "Template $handle is set to read-only mode.";

                return false;
            }
        } else {
            self::$errorMsg = "Template $handle can not be found.";

            return false;
        }
    }

    // No longer used in 7.0
    public static function editLayout($handle, $layout, $content)
    {
        if ($template = self::load($handle)) {
            if (in_array($layout, array_keys($template->layouts), true)) {
                return self::_writeLayout($handle, $layout, $content);
            } else {
                self::$errorMsg = "Layout $layout is not set with template $handle.";

                return false;
            }
        } else {
            self::$errorMsg = "Template $handle not found.";

            return false;
        }
    }

    public static function delete($handle)
    {
        $dir = dirname(self::find($handle));
        if (is_dir($dir) && is_writeable($dir)) {
            try {
                if (!(($files = @scandir($dir)) && count($files) <= 2)) {
                    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename => $cur) {
                        if (is_dir($filename)) {
                            rmdir($filename);
                        } elseif (is_file($filename)) {
                            unlink($filename);
                        }
                    }
                }

                return rmdir($dir);
            } catch (Exception $e) {
                self::$errorMsg = "Directory $dir could not be removed. Please check permissions.";

                return false;
            }
        } else {
            self::$errorMsg = "Template $handle can not be found.";

            return false;
        }
    }

    public static function listAll()
    {
        $result = array();

        foreach (new DirectoryIterator(EMAILTEMPLATES) as $dir) {
            if ($dir->isDir() && !$dir->isDot()) {
                if (file_exists($dir->getPathname() . '/' . self::getFileNameFromHandle($dir->getFilename()))) {
                    $result[$dir->getFileName()] = self::load($dir->getFileName());
                }
            }
        }

        foreach (ExtensionManager::listInstalledHandles() as $extension) {
            if (is_dir(EXTENSIONS . '/' . $extension . '/email-templates')) {
                foreach (new DirectoryIterator(EXTENSIONS . '/' . $extension . '/email-templates') as $dir) {
                    if ($dir->isDir() && !$dir->isDot()) {
                        if (file_exists($dir->getPathname() . '/' . self::getFileNameFromHandle($dir->getFilename()))) {
                            $result[$dir->getFileName()] = self::load($dir->getFileName());
                        }
                    }
                }
            }
        }

        ksort($result, SORT_STRING);

        return $result;
    }

    public static function getClassNameFromHandle($handle)
    {
        return sprintf('%sEmailTemplate', str_replace('-', '_', ucfirst(strtolower($handle))));
    }

    public static function getHandleFromFilename($filename)
    {
        return sscanf($filename, 'class.%[^.php].php');
    }

    public static function getFileNameFromHandle($handle)
    {
        return sprintf('class.%s.php', strtolower($handle));
    }

    public static function getHandleFromName($name)
    {
        return Lang::createHandle($name);
    }
    public static function getFileNameFromLayout($layout = 'html')
    {
        return sprintf('template.%s.xsl', strtolower($layout));
    }

    public static function about($name)
    {
        $classname = self::__getClassName($name);
        $path = self::__getDriverPath($name);

        if (!@file_exists($path)) return false;

        require_once($path);

        $handle = self::__getHandleFromFilename(basename($path));

        if (is_callable(array($classname, 'about'))) {
            $about = call_user_func(array($classname, 'about'));

            return array_merge($about, array('handle' => $handle));
        }

    }

    /**
     * Writes configuration values to the template configuration file.
     *
     * The name of the template to write configuration values to
     * @param string $handle
     *                          The configuration to write
     * @param string $contents
     *                          The location to write to, defaults to the workspace dir
     * @param string $file
     * @param bool   $overwrite
     *
     * @return bool
     */
    protected function _writeConfig($handle, $contents, $new = false)
    {
        if ($dir = ($new) ? EMAILTEMPLATES . '/' . $handle : dirname(self::find($handle))) {
            if (is_dir($dir) && is_writeable($dir)) {
                if ((is_writeable($dir . '/' . self::getFileNameFromHandle($handle))) || !file_exists($dir . '/' . self::getFileNameFromHandle($handle))) {
                    file_put_contents($dir . '/' . self::getFileNameFromHandle($handle), $contents);

                    return true;
                } else {
                    return false;
                    self::$errorMsg = "File $dir " . '/' . self::getFileNameFromHandle($handle) . ' can not be written to. Please check permissions';
                }
            } else {
                self::$errorMsg = "Directory $dir does not exist, or is not writeable.";

                return false;
            }
        } else {
            self::$errorMsg = "Template $handle can not be found.";

            return false;
        }
    }

    /**
     * Writes the layout to the layout file.
     *
     * The name of the template containing the layout
     * @param string $handle
     *                         The layout to write to
     * @param string $layout
     *                         The content to write to the layout file
     * @param string $contents
     *
     * @return bool
     */
    protected function _writeLayout($handle, $layout, $contents, $new = false)
    {
        if ($dir = ($new) ? EMAILTEMPLATES . '/' . $handle : dirname(self::find($handle))) {
            if (is_dir($dir) && is_writeable($dir)) {
                if ((is_writeable($dir . '/' . self::getFileNameFromLayout($layout))) || !file_exists($dir . '/' . self::getFileNameFromLayout($layout))) {
                    file_put_contents($dir . '/' . self::getFileNameFromLayout($layout), $contents);

                    return true;
                } else {
                    self::$errorMsg = "File $dir " . '/' . self::getFileNameFromLayout($layout) . ' can not be written to. Please check permissions';

                    return false;
                }
            } else {
                self::$errorMsg = "Directory $dir does not exist, or is not writeable.";

                return false;
            }
        } else {
            self::$errorMsg = "Template $handle can not be found.";

            return false;
        }
    }

    protected function _parseConfigTemplate($handle, $config)
    {
        $default_config = array(
            'datasources' => array(
            ),
            'layouts' => array(
                'html' => 'template.html.xsl',
                'plain' => 'template.plain.xsl'
            )
        );

        $config = array_merge($default_config, $config);

        $config_template = file_get_contents(ETMDIR . '/content/templates/class.tpl');

        // Author: Use the accessor function if available (Symphony 2.5)
        if (is_callable(array('Symphony', 'Author'))) {
            $author = Symphony::Author();
        } else {
            $author = Administration::instance()->Author;
        }

        $ignore_attachment_errors = 'false';
        if (isset($config['ignore-attachment-errors']) && filter_var($config['ignore-attachment-errors'], FILTER_VALIDATE_BOOLEAN)) {
            $ignore_attachment_errors =  'true';
        }

        $config_template = str_replace('<!-- CLASS NAME -->', self::getClassNameFromHandle(self::getHandleFromName($config['name'])), $config_template);
        $config_template = str_replace('<!-- NAME -->', addslashes($config['name']), $config_template);
        $config_template = str_replace('<!-- FROMNAME -->', addslashes($config['from-name']), $config_template);
        $config_template = str_replace('<!-- FROMEMAIL -->', addslashes($config['from-email-address']), $config_template);
        $config_template = str_replace('<!-- REPLYTONAME -->', addslashes($config['reply-to-name']), $config_template);
        $config_template = str_replace('<!-- REPLYTOEMAIL -->', addslashes($config['reply-to-email-address']), $config_template);
        $config_template = str_replace('<!-- ATTACHMENTS -->', addslashes($config['attachments']), $config_template);
        $config_template = str_replace('<!-- IGNORE ATTACHMENT ERRORS -->', $ignore_attachment_errors, $config_template);
        $config_template = str_replace('<!-- RECIPIENTS -->', addslashes($config['recipients']), $config_template);
        $config_template = str_replace('<!-- VERSION -->', '1.0', $config_template);
        $config_template = str_replace('<!-- AUTHOR NAME -->', addslashes($author->getFullName()), $config_template);
        $config_template = str_replace('<!-- AUTHOR WEBSITE -->', addslashes(URL), $config_template);
        $config_template = str_replace('<!-- AUTHOR EMAIL -->', addslashes($author->get('email')), $config_template);
        $config_template = str_replace('<!-- RELEASE DATE -->', DateTimeObj::getGMT('c'), $config_template);
        $config_template = str_replace('<!-- SUBJECT -->', addslashes($config['subject']), $config_template);

        $datasources = '';
        foreach ($config['datasources'] as $ds) {
            $datasources .= PHP_EOL . "        '" . addslashes($ds) ."',";
        }
        $config_template = str_replace('<!-- DATASOURCES -->', $datasources, $config_template);

        $layouts = '';
        foreach ($config['layouts'] as $tp => $lt) {
            $layouts .= PHP_EOL . "        '$tp' => '".addslashes($lt)."',";
        }
        $config_template = str_replace('<!-- LAYOUTS -->', $layouts, $config_template);

        return $config_template;
    }
}

class EmailTemplateManagerException extends Exception
{

}
