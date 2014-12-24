<?php

class isDownPlugin implements pluginInterface {

		/**
		 * Called when plugins are loaded
		 *
		 * @param mixed[]	$config
		 * @param resource 	$socket
		**/
		function init($config, $socket) {
			$this->config   = $config;
			$this->socket   = $socket;
			$this->disabled = false;
			if (!ini_get('allow_url_fopen')) {
				try {
					ini_set('allow_url_fopen', '1');
				} catch (Exception $e) {
					logMsg("Unable to enable allow_url_fopen, disabling isDownPlugin.");
					$this->disabled = true;
				}
			}
		}

		/**
		 * @return array
		 */
		function help() {
			return array(
				array(
					'command'     => 'isdown <hostname>',
					'description' => 'Sends the hostname to isup.me and returns it\'s status.'
				)
			);
		}

		/**
		 * Called about twice per second or when there are
		 * activity on the channel the bot are in.
		 *
		 * Put your jobs that needs to be run without user interaction here.
		 */
		function tick() {
		}

		/**
		 * Called when messages are posted on the channel
		 * the bot are in, or when somebody talks to it
		 *
		 * @param string $from
		 * @param string $channel
		 * @param string $msg
		 */
		function onMessage($from, $channel, $msg) {
			if ($this->disabled === true) {
				return;
			}
			if(stringStartsWith(strtolower($msg), $this->config['trigger'] . "isdown")) {
				$query = trim(str_replace("{$this->config['trigger']}isdown", "", $msg));
				if (!empty($query)) {
					$output = $this->checkPageStatus($query);
					sendMessage($this->socket, $channel, $from . ": " . $output);
				}
			}
		}

		/**
		 * Called when the bot is shutting down
		 */
		function destroy() {
		}

		/**
		 * Called when the server sends data to the bot which is *not* a channel message, useful
		 * if you want to have a plugin do it`s own communication to the server.
		 *
		 * @param string $data
		 */
		function onData($data) {
		}

		function checkPageStatus($page) {
			$data = file_get_contents("http://isup.me/" . $page);
			if (preg_match("/<div id=\"container\">[\n\r\s]*(.*)[\n\r\s]*</", $data, $matches)) {
				$output = str_replace("  ", " ", strip_tags(html_entity_decode($matches[1])));
				if ($output == "If you can see this page and still think we're down, it's just you.") {
					$output = str_replace("we're", $page . " is", $output);
				}
				return $output;
			} else {
				return "Failed to check online status for " . $page . ".";
			}
		}
}