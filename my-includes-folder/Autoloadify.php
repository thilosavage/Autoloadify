<?php
/**
 * Autoloadify
 *
 * Generate undefined class files based on predefined templates
 *
 * @package		Autoloadify
 * @author		Thilo Savage
 * @copyright		Copyright (c) 2012
 * @link		http://Autoloadify.thilosavage.com
 * @since		Version 1.0
 * @filesource
 */
 
 
// 	It's dangerous to have Autoloadify turned on in a production environment
//	Either turn it off when the site is live or piggyback your app's debug config var onto it
//define('Autoloadify_DEBUG',MY_APP_DEBUG_MODE);
define('Autoloadify_ON',true);
 
 
class Autoloadify
{
	
	// App root
	private $root;
	
	private $AutoloadifyRoot;
	
	// Path to class from root
	private $path;
	
	// Name of the class
	private $class_name;
	
	//	Directory of class templates
	private $templatesDir = "autoloadify_templates";
	
	/**
	 *	New Autoloadified directories will be written to this array so that
	 *	they will be autoloaded in the future
	 */
	public $dirs = array(
		'/'
	);
	
	/** __construct
	 *	@brief	
	 */
	 
	function __construct($root = '')
	{
	
		spl_autoload_register(array($this, 'run'));
		
		$this->AutoloadifyRoot = str_replace('\\','/',realpath(__DIR__));
		$this->root = $root ? $root : $this->AutoloadifyRoot;
		$this->root = str_replace('\\','/',$this->root);
	}
	 
	function run($class_name)
	{
		
		$this->class_name = $class_name;
		
		if (Autoloadify_ON !== true) {
			if ($this->findClass())
				require_once $this->root.$this->path.$this->class_name.".php";
			else
				throw new Exception("Oops, technical difficulties.");
		}
		
		// when form is submitted, write the new class
		if (isset($_POST['classType']))
		{
			$this->setPath($_POST['classPath']);
			$this->updateDirectories();
			$this->writeClass();
		}

		if ($this->findClass() || $this->checkClass()) {
			require_once $this->root.$this->path.$this->class_name.".php";
		}
		else {
			exit($this->renderForm());
		}
	}
	
	/** findClass
	 *	@brief	Cycle through directories to find if class already exists
	 *	@return	bool	TRUE if class is found!!!
	 */
	function findClass()
	{
		foreach ($this->dirs as $dir)
		{
			if ($this->checkClass($dir))
			{
				$this->path = $dir;
				return true;
			}
		}
		return false;
	}
	
	/** checkClass
	 *	@brief	Check if class is set based on the current path
	 *	@param	string		Directory to check
	 *	@return 	boolean		True if class file exists
	 */	
	private function checkClass($dir = null)
	{
		$dir = $dir ? $dir : $this->path;
		if (file_exists($this->root.$dir.$this->class_name.".php"))
			return true;
			return false;
	}
	
	
	/** writeDirectory
	 *	@brief	Create directories and add them to Autoloadify $dirs
	 */		
	private function updateDirectories()
	{
		// before writing class file, create directories if necessary
		// and update Autoloadify.php to search the new directories in the future
		if (!is_dir($this->root.$this->path)) {
			if ($this->createDirectory($this->root.$this->path)) 
				$this->updateAutoloadDirs();
		}	
	}
	
	/** writeClass
	 *	@brief	Write class file
	 *	@return string	Error
	 */
	private function writeClass()
	{	
		// write the class file from the template
		$fp = fopen($this->root.$this->path.$this->class_name.".php", 'w');
		$success = fwrite($fp, $this->renderTemplate());
		fclose($fp);
		
		return $success;
	}
	
	/**
	 *	createDirectory
	 *	@brief	Write class file
	 *	@param	Path to be created
	 *	@return bool	true on success
	 */
	private function createDirectory()
	{
		if (mkdir($this->root.$this->path,0700,true))
			return true;
			return false;
	}
	
	/**
	 *	updateAutoloadify
	 *	@brief	Write class file
	 *	@return	int	Bytes written (false on error)
	 */
	private function updateAutoloadDirs()
	{
		// get the contents of this file 
		$AutoloadifyContents = file_get_contents($this->AutoloadifyRoot."/Autoloadify.php");
		
		$success = true;
		
		// if the file needs to be updated, 
		if (!strpos($AutoloadifyContents,$this->path)) {
		
			// Autoloadify must be updated
			$success = false;
			
			// insert the new path into the $dirs array
			$replace = "public \$dirs = array(";
			$with = "public \$dirs = array(\n		'".$this->path."',";
			$AutoloadifyContents = str_replace($replace,$with,$AutoloadifyContents);
			
			$success = false;
			
			if (file_exists($this->AutoloadifyRoot."/Autoloadify.php")) {
				$fp = fopen($this->AutoloadifyRoot."/Autoloadify.php", 'w');
				$success = fwrite($fp, $AutoloadifyContents);
				fclose($fp);		
			}			
		}

		return $success;
		
	}
	
	/**
	 *	renderTemplate
	 *	@brief	Render the new class template file
	 *	@return	string	Class contents
	 */
	function renderTemplate()
	{
		// get filename of template
		$templateFileName = str_replace('/','_',$this->path).$_POST['classType'].".php";
		
		$includeDir = trim(str_replace($this->root,'',$this->AutoloadifyRoot),'/');
		
		if (!file_exists($this->AutoloadifyRoot."/".$this->templatesDir."/".$templateFileName)) 
			$templateFileName = str_replace($includeDir,'',$templateFileName);
		
		$templateFileName = trim(str_replace('__','_',$templateFileName),'_');
		
		$path = $this->AutoloadifyRoot."/".$this->templatesDir."/".$templateFileName;

		$template = file_get_contents($path);	
		
		// insert classname into template
		$template = str_replace("{CLASSNAME}",$this->class_name,$template);
		
		return $template;
		
	}
	
	/**
	 *	setPath
	 *	@brief	Set the path to the Class
	 *	@param	string	Path from root
	 */	
	public function setPath($path)
	{
		$this->path = $path;
	}
	
	/**
	 *	getTemplates
	 *	@brief	Get all the class templates availabe in templates folder
	 *	@return	array		An array of available templates
	 */		
	function getTemplates()
	{
	
		if ($handle = opendir($this->AutoloadifyRoot.'/'.$this->templatesDir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$templateFiles[] = $file;
				}
			}
			closedir($handle);
		}
		
		foreach ($templateFiles as $templateFile)
		{
			// underscore serves as directory indicator
			$templateSegments = explode("_",$templateFile);
			
			// excluding the last directory because it is the template name...
			$templateDirs = array_splice($templateSegments,0,-1);
			
	
			// get the path that the class will be in
			$templateDirStack = "/";
			foreach ($templateDirs as $templateDir) 
				$templateDirStack .= $templateDir."/";
			
			// get rid of .php
			$templates[substr($templateSegments[0],0,-4)] = $templateDirStack;
			
			ksort($templates);
			
		}
		
		return $templates;
	}
	
	/**
	 *	renderForm
	 *	@brief	Render the class generation form
	 *	@return	string	Form html
	 */		
	function renderForm()
	{

		$includes_folder = str_replace($this->root,'',$this->AutoloadifyRoot)."/";
			
		$html = "
		<div style='position: fixed; top: 20px; left: 20px; box-shadow: 2px 2px 20px black; background-color: white; font-size: 24px; border: 1px solid black;'>
		<form method='post'>
		<div style='text-align: center; padding: 5px; background-color: #333; color: yellow;'>
			<strong>Class ".$this->class_name." could not be found.</strong><br>
		</div>
		<div style='text-align: center; padding: 30px 0 5px;'>Would you like to create it?</div>
		<div style='padding: 20px;'>
		<div style='text-align: center; padding: 15px; font-size: 15px;'><em>Enter a path to generate the class<br>or select a template:</em>
		<input type='hidden' name='classType' id='classType' value='_default'>
		<select onchange='
			p=this.options[this.selectedIndex];
			document.getElementById(\"classType\").value = p.text;
			document.getElementById(\"classPath\").value = p.value
		'>
		";
		foreach ($this->getTemplates() as $template => $dir) {
			
			$dir = $dir == '/' ? $includes_folder : $dir;
			
			$html .= "<option value='".$dir."'";
			if ($template == 'default') { $html .= "selected"; }
			$html .= ">".$template."</option>";
		
		}
		$html .= "
		</select>	
		</div>
		<span>".$this->root."</span>
		<input type='text' name='classPath' id='classPath' value='".$includes_folder."'>".$this->class_name.".php<br>
		<div style='padding: 20px 0 0; text-align: center;'><input type='submit' value='CREATE' method='post'></div>
		</form>
		</div>
		";
		
		return $html;

	}
}

// End of file
// Autoloadify.php