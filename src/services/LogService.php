<?php

/**
 * Avatax plugin for Craft CMS 3.x
 *
 * Calculate and add sales tax to an order's base tax using Avalara's AvaTax service.
 *
 * @link      http://surprisehighway.com
 * @copyright Copyright (c) 2019 Surprise Highway
 */

namespace abryrath\avatax\services;

use abryrath\avatax\Avatax;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\FileHelper;

use yii\base\Exception;

/**
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.0
 */
class LogService extends Component
{
    // Public Properties
    // =========================================================================

    /**
     * @var settings
     */
    public $logFile;

    /**
     * @var boolean
     */
    public $debug = false;


    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->logFile = Craft::$app->path->getLogPath() . '/avatax.log';

        $settings = Avatax::$plugin->getSettings();
        $this->debug = $settings->getDebug();
    }

    /**
     * @param string $type info or error
     * @param string $message
     * @param array $data return or response data
     * @return void
     */
    public function log($type, $message, $data = [])
    {
        if ($type === 'info' && $this->debug === false) {
            return;
        }

        $date = new \DateTime();

        $log = [
            'date' => $date->format('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message,
            'data' => $data,
        ];

        FileHelper::writeToFile($this->logFile, json_encode($log) . PHP_EOL, ['append' => true]);
    }

    /**
     * @return array
     */
    public function getLogEntries()
    {
        $logEntries = [];

        App::maxPowerCaptain();

        if (@file_exists($this->logFile)) {
            $contents = @file_get_contents($this->logFile);
            $lines = explode("\n", $contents);

            foreach ($lines as $line) {
                $log = json_decode($line, true);

                if ($log) {
                    if (isset($log['data']['request'])) {
                        $log['data']['request'] = json_decode($log['data']['request']);
                    }

                    if (isset($log['data']['response'])) {
                        $log['data']['response'] = json_decode($log['data']['response']);
                    }

                    $logEntries[] = $log;
                }
            }
        }

        return array_reverse($logEntries);
    }

    /**
     * @return void
     */
    public function clearLogs()
    {
        FileHelper::unlink($this->logFile);
    }
}
