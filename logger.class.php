<?php
/**
 * Class: Logger
 * Author: adistoe
 * Website: https://www.adistoe.ch
 * Version: 1.0.0
 * Last Update: Friday, 19 January 2018
 * Description:
 *    A class to log php errors, custom messages and more.
 *    The log data can either be saved into a file or database.
 *
 * Copyright by adistoe | All rights reserved.
 */
class Logger
{
    private $db;
    private $handlePhpErrors;
    private $savePhpErrors;
    private $showPhpErrors;
    private $saveCustomMessages;
    private $showCustomMessages;
    private $errorCodes = Array(
        0     => 'CUSTOM_LOG_MESSAGE',
        1     => 'E_ERROR',
        2     => 'E_WARNING',
        4     => 'E_PARSE',
        8     => 'E_NOTICE',
        16    => 'E_CORE_ERROR',
        32    => 'E_CORE_WARNING',
        64    => 'E_COMPILE_ERROR',
        128   => 'E_COMPILE_WARNING',
        256   => 'E_USER_ERROR',
        512   => 'E_USER_WARNING',
        1024  => 'E_USER_NOTICE',
        2048  => 'E_STRICT',
        4096  => 'E_RECOVERABLE_ERROR',
        8192  => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED',
        32767 => 'E_ALL'
    );

    // Prefix (before table name) and suffix (after table name) for tables
    private $prefix = '';
    private $suffix = '';

    // Format to display dates
    private $dateFormat = 'd.m.Y - H:i:s';

    /**
     * Constructor
     * Initializes the class
     *
     * @param object $pdo Database object (PDO)
     * @param boolean $handlePhpErrors Logger handle mode for PHP errors
     * @param boolean $showPhpErrors PHP errors display mode
     * @param boolean $savePhpErrors PHP errors database save mode
     * @param boolean $showCustomMessages Log display mode
     * @param boolean $saveCustomMessages Log database save mode
     */
    public function __construct(
        $pdo,
        $handlePhpErrors = true,
        $savePhpErrors = true,
        $showPhpErrors = true,
        $saveCustomMessages = true,
        $showCustomMessages = true
    )
    {
        $this->db = $pdo;
        $this->handlePhpErrors = $handlePhpErrors;
        $this->showPhpErrors = $showPhpErrors;
        $this->savePhpErrors = $savePhpErrors;
        $this->showCustomMessages = $showCustomMessages;
        $this->saveCustomMessages = $saveCustomMessages;

        if ($this->handlePhpErrors) {
            set_error_handler(array($this, 'logPhpError'));
            register_shutdown_function(array($this, 'logPhpShutdownError'));
        }
    }

    /**
     * Creates needed database tables for log mode "database"
     */
    public function createTables() {
        if ($this->db->query('
            CREATE TABLE ' . $this->prefix . 'log' . $this->suffix . '(
                ID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                level INT,
                message TEXT,
                file TEXT,
                line INT,
                date DATETIME NOT NULL DEFAULT NOW()
            )
        ')) {
            return true;
        }

        return false;
    }

    /**
     * Get full error log
     *
     * @param string $orderColumn Order results by given column
     * @param string $orderDirection Order results in given direction
     * @param string $limit Show only given amount of records
     *
     * @return string[] Returns log
     */
    public function getLog(
        $orderColumn = 'date',
        $orderDirection = 'DESC',
        $limit = ''
    ) {
        $log = Array();
        $stmt = $this->db->prepare("
            SELECT
                *
            FROM " . $this->prefix . 'log' . $this->suffix . "
            ORDER BY $orderColumn $orderDirection $limit
        ");

        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['date'] = date($this->dateFormat, strtotime($row['date']));
            $row['level_description'] = $this->errorCodes[$row['level']];
            $log[$row['ID']] = $row;
        }

        return $log;
    }

    /**
     * Logs custom (user specified) messages
     *
     * @param $msg Message
     * @param $file File
     * @param $line Line number
     * @param $level Error level (Shouldn't be changed normally)
     */
    public function logCustomMessage($msg, $file = NULL, $line = NULL, $level = 0) {
        if ($this->showCustomMessages) {
            echo
                '<hr>
                <p>
                    <b style="font-weight: bold">[Logger] Custom Message:</b>
                </p>
                <table>
                    <tbody>
                        <tr>
                            <td>
                                Level:
                            </td>
                            <td>' .
                                $this->errorCodes[$level] . ' (' . $level . ')
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Message:
                            </td>
                            <td>
                                ' . $msg . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                File:
                            </td>
                            <td>
                                ' . $file . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Line:
                            </td>
                            <td>
                                ' . $line . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Date:
                            </td>
                            <td>
                                ' . date($this->dateFormat, time()) . '
                            </td>
                        </tr>
                    </tbody>
                </table>
                <hr>';
        }

        if ($this->saveCustomMessages) {
            $stmt = $this->db->prepare('
                INSERT INTO ' . $this->prefix . 'log' . $this->suffix . '(
                    level,
                    message,
                    file,
                    line
                ) VALUES (
                    :level,
                    :message,
                    :file,
                    :line
                )
            ');

            $stmt->bindParam(':level', $level);
            $stmt->bindParam(':message', $msg);
            $stmt->bindParam(':file', $file);
            $stmt->bindParam(':line', $line);
            $stmt->execute();

            if ($stmt->rowCount() <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Logs errors from PHP
     *
     * @param $level Error level
     * @param $msg Error message
     * @param $file File in which the error happened
     * @param $line Line number of the error
     */
    public function logPhpError($level, $msg, $file, $line) {
        if ($this->showPhpErrors) {
            echo
                '<hr>
                <p>
                    <b style="font-weight: bold">[Logger] PHP Error:</b>
                </p>
                <table>
                    <tbody>
                        <tr>
                            <td>
                                Level:
                            </td>
                            <td>' .
                                $this->errorCodes[$level] . ' (' . $level . ')
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Message:
                            </td>
                            <td>
                                ' . $msg . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                File:
                            </td>
                            <td>
                                ' . $file . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Line:
                            </td>
                            <td>
                                ' . $line . '
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Date:
                            </td>
                            <td>
                                ' . date($this->dateFormat, time()) . '
                            </td>
                        </tr>
                    </tbody>
                </table>
                <hr>';
        }

        if ($this->savePhpErrors) {
            $stmt = $this->db->prepare('
                INSERT INTO ' . $this->prefix . 'log' . $this->suffix . '(
                    level,
                    message,
                    file,
                    line
                ) VALUES (
                    :level,
                    :message,
                    :file,
                    :line
                )
            ');

            $stmt->bindParam(':level', $level);
            $stmt->bindParam(':message', $msg);
            $stmt->bindParam(':file', $file);
            $stmt->bindParam(':line', $line);
            $stmt->execute();

            if ($stmt->rowCount() <= 0) {
                return false;
            }
        }

        return true;
    }


    /**
     * Logs errors from PHP causing the script to stop (like FATAL ERRORs)
     *
     */
    public function logPhpShutdownError() {
        $error = error_get_last();

        if (!empty($error)) {
            $this->logPhpError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }
}
