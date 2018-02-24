<?php

/**
* 	Basic Jinja2 template engine support
*	for PHP by Marcin Poliński
*	Supported tags:
*		{{ var_name }} - single value or indexed {{ t[0] }} or {{ t['key'] }}
*		{{ extends path/view#blockname }} - path from app/views/
*		{% if %}
*		{% for value in array %}
*		{% for key,values in dict %}
*		{{ forloop.counter }}
*		{{ forloop.counter0 }}
*	mail: dwyanepolinski@gmail.com
*/

class Jinja{

	public $template = NULL;
	public $data = NULL;
	const ctags = ['if', 'for'];
	const etags = ['elif', 'else'];
	const ftags = ['endif', 'endfor'];
	
	public function __construct($_template, $_data){
		$this->template = $_template;
		$this->data = $_data;
	}

	public function get_position($template, $tag1, $tag2){
		return [strpos($template, $tag1), strrpos($template, $tag2)];
	}

	/* extend all template data from extend blocks */
	public function extend($template){
		$template = (new Render($template))->page;

		preg_match_all('{{ extends (.{1,100}) }}', $template, $extending);

		foreach ($extending[0] as $key => $value) {
			$template = str_replace('{'.$value.'}', '', $template);

			$tag_data = explode('#', $extending[1][$key]);
			$block_tag = '{{ block '.$tag_data[1].' }}';

			$extended_template = $this->extend($tag_data[0]);

			$temp_blk_pos = $this->get_position($template, $block_tag, '{{ endblock }}');
			$temp_blk_pos[0] += strlen($block_tag);
			$temp_blk_content = substr($template, $temp_blk_pos[0], $temp_blk_pos[1] - $temp_blk_pos[0]);

			$stemp_blk_pos = $this->get_position($extended_template, $block_tag, '{{ endblock }}');
			$stemp_blk_pos[1] += 14;

			$template = substr_replace($extended_template,
				$temp_blk_content,
				$stemp_blk_pos[0],
				$stemp_blk_pos[1] - $stemp_blk_pos[0]);
		}

		return $template;
	}

	public function put_incstruction_code($template, $code_buffer, $positions, $iter){
		$prev_tag = array_values(array_slice(array_keys($code_buffer), -1))[0];
		$prev_tag_pos = end($positions);
		$prev_tag_end_pos = $prev_tag_pos + strlen($prev_tag) + 6;
		$code_buffer[$prev_tag] = trim(substr($template, $prev_tag_end_pos, $iter - $prev_tag_end_pos), " \n");
		return $code_buffer;
	}

	public function declare_array($arr){
		$code = '[';
		foreach ($arr as $key => $value) {
			if(!is_numeric($key))
				$key = "'$key'";
			if(!is_numeric($value)){
				if(!is_array($value))
					$value = "'$value'";
				else
					$value = $this->declare_array($value);
			}
			$code .= "$key => $value, ";
		}
		$code[strlen($code) - 2] = ']';
		return $code;
	}

	public function get_forloopcounter(){
		while (true) {
			$var_name = str_shuffle('abc_XYZ');
			if(!in_array($var_name, $this->data))
				return $var_name;
		}
	}

	public function jinja2php($template){

		$tag = '';
		$tag_type = '';
		$instruction_list = [];
		$code_buffer = [];
		$positions = [];
		$skip_instructions = 0;
		$template_length = strlen($template);

		/** Create array $instruction_list witch includes array with instructions
		of first layer (depth = 0). Syntax $instruction_list = array('[pos_start:pos_stop]' => code_inside). **/

		for($i = 0; $i < $template_length - 1; $i++){
			$cursor = $template[$i].$template[$i+1];

			if($cursor == '{{'){
				$subtemplate = substr($template, $i);
				$vtag_end_pos = $i + strpos($subtemplate, '}}');
				$vtag = substr($template, $i + 3, $vtag_end_pos - $i - 4);
				if($tag_type == 'for' || $tag_type == 'endfor'){
					if($skip_instructions == 0)
						$template[$i + 1] = $template[$vtag_end_pos] = '#';
				}
			}

			if($cursor == '{%'){
				$subtemplate = substr($template, $i);
				$tag_end_pos = $i + strpos($subtemplate, '%}');
				$tag = substr($template, $i + 3, $tag_end_pos - $i - 4);
				$tag_type = explode(' ', $tag)[0];

				if(in_array($tag_type, self::ctags)){
					if(!empty($code_buffer)){
						$skip_instructions++;
						continue;
					}
					$code_buffer[$tag] = '';
					$positions[] = $i;
				}
				if(in_array($tag_type, self::etags)){
					if($skip_instructions > 0)
						continue;
					$code_buffer = $this->put_incstruction_code($template, $code_buffer, $positions, $i);
					$code_buffer[$tag] = '';
					$positions[] = $i;
				}
				if(in_array($tag_type, self::ftags)){
					if($skip_instructions > 0){
						$skip_instructions--;
						continue;
					}
					$code_buffer = $this->put_incstruction_code($template, $code_buffer, $positions, $i);
					$instruction_list[$positions[0].':'.($i + strlen($tag) + 6)] = $code_buffer;
					$code_buffer = [];
					$positions = [];
				}

				$i = $tag_end_pos + 2;
			}
		}

		$positions_offset = 0;

		/** Handle instructions from $instruction_list array **/

		foreach ($instruction_list as $positions => $code_buffer) {
			$php_code = '';
			foreach ($this->data as $key => $value) {
				if(!is_numeric($value)){
					if(!is_array($value))
						$value = "\"$value\"";
					else
						$value = $this->declare_array($value);
				}
				$php_code .= "$$key = $value;\n";
			}

			$instruction = array_keys($code_buffer)[0];
			$instruction_parted = preg_split('/ +/', $instruction);

			if($instruction_parted[0] == 'if'){
				$php_code .= 'if($'.substr($instruction, 3).")return 0;\n";
				$code_parts = count($code_buffer);
				if($code_parts > 1){
					$i = 1;
					foreach (array_slice($code_buffer, 1) as $subinstruction => $value) {
						if($code_parts - $i == 1)
							$condition = ' ';
						else
							$condition = '($'.substr($subinstruction, 5).')';
						$php_code .= str_replace('elif', 'elseif', explode(' ', $subinstruction)[0]).$condition."return $i;\n";
						$i++;
					}			
				}				
				$i = 1;
				$instruction_return_code = array_values($code_buffer)[eval($php_code)];
			}
			else{
				$arr = end($instruction_parted);
				$forloop_counter = $this->get_forloopcounter();
				$php_code .= "$$forloop_counter = 0;foreach ($$arr as ";

				$iterators = explode(',', $instruction);
				if(count($iterators) == 2){
					$iterators[0] = array_values(array_slice(preg_split('/ +/', trim($iterators[0])), -1))[0];
					$iterators[1] = preg_split('/ +/', trim($iterators[1]))[0];
					$php_code .= "$$iterators[0] => $$iterators[1]){";
					$php_code .= "if(is_array($$iterators[1]))
									echo str_replace('{# forloop.counter0 #}',$$forloop_counter ,
									str_replace('{# forloop.counter #}', $$forloop_counter + 1,
									str_replace('{# $iterators[1] #}', $$iterators[1],
									str_replace('{# $iterators[0] #}', json_encode($$iterators[0]), '$code_buffer[$instruction]'))));
								else echo str_replace('{# forloop.counter0 #}',$$forloop_counter ,
									str_replace('{# forloop.counter #}', $$forloop_counter + 1,
									str_replace('{# $iterators[1] #}', $$iterators[1],
									str_replace('{# $iterators[0] #}', $$iterators[0], '$code_buffer[$instruction]'))));$$forloop_counter++;}";
				} else{
					$php_code .= "$$instruction_parted[1]){";
					$php_code .= "if(is_array($$instruction_parted[1]))
									echo str_replace('{# forloop.counter0 #}',$$forloop_counter ,
									str_replace('{# forloop.counter #}', $$forloop_counter + 1,
									str_replace('{# $instruction_parted[1] #}', json_encode($$instruction_parted[1]), '$code_buffer[$instruction]')));
								else echo str_replace('{# forloop.counter0 #}',$$forloop_counter ,
									str_replace('{# forloop.counter #}', $$forloop_counter + 1,
									str_replace('{# $instruction_parted[1] #}', $$instruction_parted[1], '$code_buffer[$instruction]')));$$forloop_counter++;}";
				}

				ob_start();
				eval($php_code);
				$instruction_return_code = ob_get_contents();
				ob_end_clean();
			}

			/** Update template after jinja code execution **/

			$positions = explode(':', $positions);
			$positions[0] -= $positions_offset;
			$positions[1] -= $positions_offset;

			$template_length = strlen($template);

			$template = substr_replace($template, $instruction_return_code, $positions[0], $positions[1] - $positions[0]);
			$new_template_length = strlen($template);
			$positions_offset += $template_length - $new_template_length;
		}

		preg_match('{% (for|if) (.{1,50}) %}', $template, $jinja_operations);

		/** If found some Jinja syntax in template, do jinja2php on it by recursion **/

		if(!empty($jinja_operations))
			$template = $this->jinja2php($template);

		return $template;
	}

	public function render(){
		$this->template = $this->extend($this->template);
		$this->template = $this->jinja2php($this->template);
		echo $this->template;
	}
}

?>