<?php

require_once(TOOLKIT . '/class.manager.php');

Class EmailTemplateManager extends Manager{

	public function listAll(){
		$result = Array();
		foreach(new DirectoryIterator(EXTENSIONS . "/email_templates/templates") as $dir){
			if($dir->isDir() && !$dir->isDot()){
				if(file_exists($dir->getPathname() . '/config.php') && file_exists($dir->getPathname() . '/template.xsl')){
					$result[] = $dir->getFileName();
				}
			}	
		}
		return $result;
	}
}