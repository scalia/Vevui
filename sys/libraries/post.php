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

class Post extends Lib
{
	private $_post;
	private $_rules;
	private $_errors;

	function  __construct()
	{
		$this->_post = $_POST;
		$this->_rules = array();
		$this->_errors = array();
		$this->_min_len_rules = array();
		$this->_max_len_rules = array();		
		$this->_curname = '';
	}

	function __get($name)
	{
		return array_key_exists($name, $this->_post)?$this->_post[$name]:NULL;
	}

	function min($len)
	{
		$this->_min_len_rules[$this->_curname] = $len;
		return $this;
	}
	
	function max($len)
	{
		$this->_max_len_rules[$this->_curname] = $len;
		return $this;		
	}	
	
	function rule($name, $required, $_ = NULL)
	{
		$this->_rules[] = array($name, $required, $_?array_slice(func_get_args(), 2):array());
		$this->_curname = $name;
		return $this;
	}

	function check()
	{
		foreach($this->_rules as $rule)
		{
			$name = $rule[0];
			$required = $rule[1];
			$funcs = $rule[2];

			if ( $required && ((!array_key_exists($name, $this->_post)) || (''===$this->_post[$name])) )
			{
				$this->_errors[ $name . '_error' ] = TRUE;
			}

			if (array_key_exists($name, $this->_min_len_rules))
			{
				$minlen = $this->_min_len_rules[$name];
				if(strlen($this->_post[$name]) < $minlen)
					$this->_errors[ $name . '_error' ] = TRUE;
			}

			if (array_key_exists($name, $this->_max_len_rules))
			{
				$maxlen = $this->_max_len_rules[$name];
				if(strlen($this->_post[$name]) > $maxlen)
					$this->_errors[ $name . '_error' ] = TRUE;
			}			
			
			if (array_key_exists($name, $this->_post))
			{
				$param = $this->_post[$name];
				foreach($funcs as $func)
				{
					$res = call_user_func($func, $param);
					if (is_bool($res))
					{
						if (!$res)
						{
							$this->_errors[ $name . '_error' ] = TRUE;
						}
					}
					else
					{
						if('' === $res)
							$this->_errors[ $name . '_error' ] = TRUE;
						else
							$param = $res;
					}
				}
				$this->_post[$name] = $param;
			}
		}
		return empty($this->_errors);
	}
	
	function get_errors()
	{
		return $this->_errors;
	}
}

/* End of file sys/libraries/post.php */
