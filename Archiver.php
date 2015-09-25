<?php
/**
* User: Zbigniew 'zibi' Jarosik <zibi@nora.pl>
* Date: 07.05.15 15:19
 */

namespace IFR\Main;

if( !class_exists('Archiver') )
{
	class Archiver
	{
		static $_config = [
			'zip'=>[
				'compress'=>'zip -r',
				'decompress'=>'unzip',
				'pipe'=>'unzip -p',
				'list'=>'unzip -l',
				'list_options'=>['column'=>4,'skip'=>3],
				'target'=>'-d',
				'ext'=>'zip'
			],
			'tar.gz'=>[
				'compress'=>'tar -cvzf',
				'decompress'=>'tar --wildcards -zxf',
				'pipe'=>'tar -zOxf',
				'list'=>'tar -ztvf',
				'list_options'=>['column'=>6],
				'target'=>'-C',
				'ext'=>'tar.gz'
			]
		];

		public $config = null;
		public $files = [];
		public $archiveFile = '';
		public $target = '';
		public $fileList = [];

		public function __construct()
		{
		}

		public function open($fileName)
		{
			$found = false;

			if(file_exists($fileName))
			{
				$found = true;
			}
			else
			{
				foreach(self::$_config as $archConf)
				{
					$fileName = "{$fileName}.{$archConf['ext']}";
					if(file_exists($fileName))
					{
						$found = true;
						break;
					}
				}
			}

			if(!$found)
			{
				throw new \Exception("Backup file '$fileName' doesn't exists");
			}

			foreach(self::$_config as $type=>$archConf)
			{
				if(str_endswith($fileName,$archConf['ext']))
				{
					$this->setType($type);
					break;
				}
			}

			$this->setName(dirname($fileName).'/'.basename($fileName,'.'.$this->config->ext));

			return $this;
		}

		public function getFileList()
		{
			if(empty($this->fileList))
			{
				$exe = "{$this->config->list} {$this->getFileName()} | awk '{print \${$this->config->list_options->column}}'";
				proc_exec( $exe , $this->fileList );
				$this->fileList = array_filter($this->fileList,'strlen');

				if(isset($this->config->list_options->skip))
				{
					$this->fileList = array_slice($this->fileList,$this->config->list_options->skip);
				}

				$this->fileList = array_combine($this->fileList,$this->fileList);
			}

			return $this->fileList;
		}


		public function setName($archiveFile)
		{
			$this->archiveFile = $archiveFile;

			return $this;
		}
		public function setTarget($targetPath)
		{
			$this->target = $targetPath;

			return $this;
		}

		public function setType($archiverName)
		{
			if(array_key_exists($archiverName,self::$_config))
			{
				$this->config = object(self::$_config[$archiverName]);
			}

			return $this;
		}

		public function resetFiles()
		{
			$this->files = [];

			return $this;
		}

		public function addFile($path=[])
		{
			if(!is_array($path))
			{
				$path = [$path];
			}

			foreach($path as $file)
			{
				$this->files[] = $file;
			}

			return $this;
		}


		function getDecompressCommand()
		{
			$exe = "{$this->config->decompress} {$this->getName()} ".join(' ',$this->files);

			return $exe;
		}

		function getPipeCommand()
		{
			$exe = "{$this->config->pipe} {$this->getName()} ".join(' ',$this->files);

			return $exe;
		}

		function compress()
		{
			$exe = "nice {$this->config->compress} {$this->getName()} ".join(' ',$this->files);
			proc_exec($exe);

			return $this;
		}

		function getFilename()
		{
			return "{$this->archiveFile}.{$this->config->ext}";
		}
		function getName()
		{
			return $this->archiveFile;
		}
		function getExtension()
		{
			return $this->config->ext;
		}
		static function getExtensionList()
		{
			return array_map(
				function($item)
				{
					return $item['ext'];
				},
				self::$_config
			);
		}
		function fileExists($file)
		{
			if(empty($this->fileList))
			{
				$this->getFileList();
			}

			return array_key_exists($file,$this->fileList);
		}
		static function isArchive($fileName)
		{
			$ret = false;

			foreach(self::$_config as $archConf)
			{
				if(str_endswith($fileName,$archConf['ext']))
				{
					$ret = true;
					break;
				}
			}

			return $ret;
		}
		function decompress()
		{
			$target = $this->target?
				" {$this->config->target} {$this->target} ":
				''
			;
			$exe = "{$this->config->decompress} {$this->getFilename()} {$target}  ".join(' ',$this->files);

			proc_exec($exe);

			return $this;
		}

		function pipeTo($cmdList)
		{
			if(!is_array($cmdList))
			{
				$cmdList = [$cmdList];
			}

			$exe = "{$this->config->pipe} {$this->getFilename()} ".join(' ',$this->files);
			array_unshift($cmdList,$exe);
			$exe = join(' | ',$cmdList);

			proc_exec($exe);
		}
	}
}
