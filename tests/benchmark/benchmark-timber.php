<?php

	class TimberBenchmark {

		static function testLoader(){
			$TimberLoader = new Timber\Loader();
			for ($i = 0; $i<5000; $i++){
				$loader = $TimberLoader->get_loader();
			}
		}

		public static function run($function){
			$start_time = microtime(true);
			self::$function();
			$end_time = microtime(true);
			echo $end_time - $start_time;
		}

	}


