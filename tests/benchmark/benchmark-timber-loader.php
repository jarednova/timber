<?php

	class TimberBenchmark {

		static function testLoader(){
			$TimberLoader = new Timber\Loader();
			for ($i = 0; $i<5000; $i++){
				$loader = $TimberLoader->get_loader();
			}
		}

		static function testImageResize(){
			$img = 'arch.jpg';
			$upload_dir = wp_upload_dir();
			$destination = $upload_dir['path'].'/'.$img;
			if (!file_exists($destination)){
				copy(__DIR__.'/../assets/'.$img, $destination);
			}
			for ($i = 0; $i<20; $i++){
				$upload_dir = wp_upload_dir();
				$img = Timber\ImageHelper::resize($upload_dir['url'].'/arch.jpg', 500, 200, 'default', true);
			}
		}

		public static function run($function){
			$start_time = microtime(true);
			self::$function();
			$end_time = microtime(true);
			echo $end_time - $start_time;
		}

	}


