<?php

namespace cabezayunke\yii\redisHa;

use yii\db\Exception;

class SentinelsManager {

	/**
	 * Facade function for interraction with sentinel.
	 *
	 * Connects to sentinels (iterrates them if ones fail) and asks for master server address.
	 *
	 * @return array [host,port] address of redis master server or throws exception.
	 **/
	function discoverMaster ($sentinels, $masterName, $sentinelsPassword = null) {
        \Yii::info("Discovering master $masterName", __METHOD__);

        foreach ($sentinels as $sentinel) {
            \Yii::info("Sentinel:", __METHOD__);
            \Yii::info($sentinel, __METHOD__);

            if (is_scalar($sentinel)) {
				$sentinel = [
						'hostname' => $sentinel,
                        'port' => 26379
				];
			}
			$connection = new SentinelConnection();
			$connection->hostname = isset($sentinel['hostname']) ? $sentinel['hostname'] : null;
            $connection->port = isset($sentinel['port']) ? (int) $sentinel['port'] : 26379;
            $connection->masterName = $masterName;
			$connection->connectionTimeout = isset($sentinel['connectionTimeout']) ? $sentinel['connectionTimeout'] : null;
			$connection->unixSocket = isset($sentinel['unixSocket']) ? $sentinel['unixSocket'] : null;
			if(!empty($sentinelsPassword)) {
			    $connection->password = $sentinelsPassword;
            }
			$r = $connection->getMaster();
			if (isset($sentinel['hostname'])) {
				$connectionName = "{$connection->hostname}:{$connection->port}";
			} else {
				$connectionName = $connection->unixSocket;
			}
			if ($r) {
				\Yii::info("Sentinel @{$connectionName} gave master addr: {$r[0]}:{$r[1]}", __METHOD__);
				return $r;
			} else {
				\Yii::info("Did not get any master from sentinel @{$connectionName}", __METHOD__);
			}
		}
		throw new \Exception("Master could not be discovered");
	}
}
