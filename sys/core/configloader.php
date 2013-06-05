<?php
/*************************************************************************
 Copyright 2011 Vevui Development Team

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
*************************************************************************/

class ConfigLoader
{
	const VERSION = 1;

	private function _merge_config(&$config, $env)
	{
		foreach($env as $key => $value)
		{
			if (is_object($config))
			{
				if (property_exists($config, $key))
				{
					if (is_scalar($value))
					{
						$config->{$key} = $value;
					}
					else
					{
						$this->_merge_config($config->{$key}, $value);
					}
				}
				else
				{
					$config->{$key} = $value;
				}
			}
			else
			{
				if (array_key_exists($key, $config))
				{
					if (is_scalar($value))
					{
						$config[$key] = $value;
					}
					else
					{
						$this->_merge_config($config[$key], $value);
					}
				}
				else
				{
					$config[$key] = $value;
				}
			}
		}
	}

	function __construct()
	{
		$config_file_path = CACHE_PATH.'/config.php';

		$core = & Vevui::get();
		$core->disable_errors();
		if(file_exists($config_file_path))
		{
			@include($config_file_path);
		}

		$core->enable_errors();

		if (isset($config) && self::VERSION === $config->_vevui->version && ENVIRONMENT === $config->_vevui->environment)
		{
			$config_ok = TRUE;
		}
		else
		{
			$config_ok = FALSE;
			$config = new stdClass();
		}

		// Skip checking for modifications.
		if ( (!$config_ok) || ($config->app->config_ttl && ($config->_vevui->timestamp+$config->app->config_ttl) < time()) )
		{
			$config_mtime = $config_ok?$config->_vevui->timestamp:FALSE;

			$files = @scandir(APP_CONFIG_PATH);
			$modified = FALSE;
			$yaml_files = array();
			foreach($files as $file)
			{
				if (0!==strcasecmp(substr($file, -5), '.yaml')) continue;
				$name = strtolower(substr($file, 0, -5));
				$yaml_files[$name] = TRUE;

				if ( (FALSE === $config_mtime) || ($config_mtime < @filemtime(APP_CONFIG_PATH.'/'.$file)) || (!property_exists($config, $name)) )
				{
					$config->{$name} = $core->l->yaml->load(APP_CONFIG_PATH.'/'.$file, 'haanga' != $name);
				}
			}

			// Delete old entries that no longer exist as files.
			foreach($config as $key => $value)
			{
				if ( ('_vevui' != $key) && (!array_key_exists($key, $yaml_files)) )
				{
					unset($config->{$key});
				}
			}

			$this->_merge_config($config, $core->l->yaml->load(APP_CONFIG_PATH.'/envs/'.ENVIRONMENT.'.yaml'));

			if ($config->app->cache)
			{
				$config->_vevui = new stdClass();
				$config->_vevui->version = self::VERSION;
				$config->_vevui->timestamp = time();
				$config->_vevui->environment = ENVIRONMENT;

				// Save config atomically. If not possible, we still work but very slow!
				$tempname = tempnam(CACHE_PATH, '_config.php.'.microtime(TRUE));

				$content = '<?php $config = unserialize(\''.str_replace('\'', '\\\'', serialize($config))."');\n".'// Generated by Vevui '.VEVUI_VERSION.' on '.date('c');
				$core->disable_errors();
				@file_put_contents($tempname, $content);
				@rename($tempname, $config_file_path);
				$core->enable_errors();
			}
		}

		unset($config->_vevui);

		// Assign config to local fields.
		foreach($config as $key => $value)
		{
			$this->{$key} = $value;
		}
	}
}

/* End of file sys/core/configloader.php */
