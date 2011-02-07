<?php

if(!defined('EMAILTEMPLATES')) define('EMAILTEMPLATES',EXTENSIONS . "/email_templates");
require_once(TOOLKIT . '/class.manager.php');
require_once(EMAILTEMPLATES . '/lib/class.emailtemplate.php');

Class EmailTemplateManager extends Manager{
	
	public function load($handle){
		$filename = self::getFileNameFromHandle($handle);
		$classname = self::getClassNameFromHandle($handle);
		if(is_dir(EMAILTEMPLATES . "/templates/$handle")){
			if(file_exists(EMAILTEMPLATES . "/templates/$handle/$filename")){
				include_once(EMAILTEMPLATES . "/templates/$handle/$filename");
				if(class_exists($classname)){
					return new $classname;
				}
				else{
					throw new EmailTemplateManagerException("Class $classname not set in file $filename");
				}
			}
			else{
				throw new EmailTemplateManagerException("File $filename not set for template $handle");
			}
		}
		else{
			throw new EmailTemplateManagerException("Template $handle not found");
		}	
	}
	
	public function create($handle, $config){
	}
	
	public function edit($handle, $config){
	}

	public function listAll(){
		$result = Array();
		foreach(new DirectoryIterator(EMAILTEMPLATES . "/templates") as $dir){
			if($dir->isDir() && !$dir->isDot()){
				if(file_exists($dir->getPathname() . '/config.php') && file_exists($dir->getPathname() . '/template.xsl')){
					$result[] = $dir->getFileName();
				}
			}	
		}
		return $result;
	}
	
	public function getClassNameFromHandle($handle){
		return sprintf('%sEmailTemplate', ucfirst(strtolower($handle)));
	}
	
	public function getHandleFromFilename($filename){
		return sscanf($filename, 'template.%[^.php].php');
	}
	
	public function getFileNameFromHandle($handle){
		return sprintf('class.%s.php', strtolower($handle));
	}
}

Class EmailTemplateManagerException extends Exception{
}