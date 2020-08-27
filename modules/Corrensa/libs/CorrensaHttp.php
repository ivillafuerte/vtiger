<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once 'modules/Corrensa/Corrensa.php';

class CorrensaHttp
{
	public static function post($url, $params = array())
	{
		$stream = curl_init();
		curl_setopt($stream, CURLOPT_URL, $url);
		curl_setopt($stream, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($stream, CURLOPT_POST, 1);
		curl_setopt($stream, CURLOPT_POSTFIELDS, $params);
		curl_setopt($stream, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($stream, CURLOPT_SSL_VERIFYHOST, false);
		$output = curl_exec($stream);

//        if(strpos($url, "cnt/ping") === false && Corrensa::$ENABLE_LOG) {
//            $logData = array(
//                'url'       => $url,
//                'params'    => $params,
//                'output'    => $output
//            );
//
//            file_put_contents("modules/Corrensa/logs/http/output-".date('m-d-Y-H-i-s').".txt", json_encode($logData));
//        }

		curl_close($stream);

		try {
			$outputData = json_decode($output, true);

			return $outputData;
		} catch (Exception $ex) {
			throw new Exception("Can not parse json | " . $ex->getMessage());
		}
	}

    public static function get($url, $params = array())
    {
        $stream = curl_init();
        curl_setopt($stream, CURLOPT_HEADER, 0);
        curl_setopt($stream, CURLOPT_URL, $url);
        curl_setopt($stream, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($stream, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($stream, CURLOPT_SSL_VERIFYHOST, false);
        $output = curl_exec($stream);

        curl_close($stream);

        try {
            $outputData = json_decode($output, true);

            return $outputData;
        } catch (Exception $ex) {
            throw new Exception("Can not parse json | " . $ex->getMessage());
        }
    }
}