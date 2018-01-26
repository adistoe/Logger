<?php
/**
 * Class: Logger
 * Author: adistoe
 * Website: https://www.adistoe.ch
 * Version: 1.0.1
 * Last Update: Thursday, 23 January 2018
 * Description:
 *    A class to log php errors, custom messages and more.
 *
 * Copyright by adistoe | All rights reserved.
 */
class Logger
{
    /**
     * ========================================
     * ============ Configuration =============
     * ========================================
     *
     * Change the following part to fit your requirements.
     */

    // =======================
    // ===== PHP errors ======
    // =======================

    // Specifies if PHP errors should be handled by this class
    private $handlePhpErrors = true;

    // Specifies if PHP errors should be saved
    private $savePhpErrors = true;

    // Specifies if PHP errors should be displayed on the screen
    private $showPhpErrors = true;

    // =======================
    // === Custom messages ===
    // =======================

    // Specifies if the custom log messages should be saved
    private $saveCustomMessages = true;

    // Specifies if the custom log messages should be displayed on the screen
    private $showCustomMessages = true;

    // =======================
    // ====== Database =======
    // =======================

    // Prefix and suffix for tables
    private $prefix = '';
    private $suffix = '';

    // =======================
    // ======== Other ========
    // =======================

    // Specifies, which errors should be logged and which ignored
    private $errorLogActiveStatus = Array(
        'E_ERROR'             => true,
        'E_WARNING'           => true,
        'E_PARSE'             => true,
        'E_NOTICE'            => true,
        'E_CORE_ERROR'        => true,
        'E_CORE_WARNING'      => true,
        'E_COMPILE_ERROR'     => true,
        'E_COMPILE_WARNING'   => true,
        'E_USER_ERROR'        => true,
        'E_USER_WARNING'      => true,
        'E_USER_NOTICE'       => true,
        'E_STRICT'            => true,
        'E_RECOVERABLE_ERROR' => true,
        'E_DEPRECATED'        => true,
        'E_USER_DEPRECATED'   => true,
        'E_ALL'               => true
    );

    // Format to display dates
    private $dateFormat = 'd.m.Y - H:i:s';

    /**
     * ========================================
     * ======= End of the configuration =======
     * ========================================
     */

    // Do not touch these variables
    private $db;
    private $notes;
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

    /**
     * Constructor
     * Initializes the class
     *
     * @param object $pdo Database object (PDO)
     * @param object $notes Additional information to be saved on logging
     */
    public function __construct($pdo, $notes = NULL)
    {
        $this->db = $pdo;
        $this->notes = $notes;

        if ($this->handlePhpErrors) {
            set_error_handler(array($this, 'logPhpError'));
            register_shutdown_function(array($this, 'logPhpShutdownError'));
        }
    }

    /**
     * Creates needed database tables
     *
     * @return boolean Returns if the tables were created
     */
    public function createDatabaseTables()
    {
        if ($this->db->query('
            CREATE TABLE ' . $this->prefix . 'log' . $this->suffix . '(
                ID INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                level INT UNSIGNED,
                message TEXT,
                file TEXT,
                line INT UNSIGNED,
                notes TEXT,
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
     * @param string $msg Message
     * @param string $file File
     * @param int $line Line number
     * @param string $notes Additional information
     *
     * @return boolean Returns if the log entry was handled correctly
     */
    public function logCustomMessage(
        $msg,
        $file = NULL,
        $line = NULL,
        $notes = NULL
    )
    {
        if ($notes == NULL) {
            $notes = $this->notes;
        }

        if ($this->showCustomMessages) {
            $htmlNotes = '';

            if ($notes != NULL) {
                $htmlNotes =
                    "<tr>
                        <td>
                            Notes:
                        </td>
                        <td>
                            $notes
                        </td>
                    </tr>";
            }

            echo
                "<hr>
                <p>
                    <b style=\"font-weight: bold\">[Logger] PHP Error:</b>
                </p>
                <table>
                    <tbody>
                        <tr>
                            <td>
                                Level:
                            </td>
                            <td>
                                " . $this->errorCodes[0] . " (0)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Message:
                            </td>
                            <td>
                                $msg
                            </td>
                        </tr>
                        <tr>
                            <td>
                                File:
                            </td>
                            <td>
                                $file
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Line:
                            </td>
                            <td>
                                $line
                            </td>
                        </tr>
                        $htmlNotes
                        <tr>
                            <td>
                                Date:
                            </td>
                            <td>
                                " . date($this->dateFormat, time()) . "
                            </td>
                        </tr>
                    </tbody>
                </table>
                <hr>";
        }

        if ($this->saveCustomMessages) {
            $stmt = $this->db->prepare('
                INSERT INTO ' . $this->prefix . 'log' . $this->suffix . '(
                    level,
                    message,
                    file,
                    line,
                    notes
                ) VALUES (
                    0,
                    :message,
                    :file,
                    :line,
                    :notes
                )
            ');

            $stmt->bindParam(':message', $msg);
            $stmt->bindParam(':file', $file);
            $stmt->bindParam(':line', $line);
            $stmt->bindParam(':notes', $notes);
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
     * @param int $level Error level
     * @param string $msg Error message
     * @param string $file File in which the error happened
     * @param string $line Line number of the error
     * @param string[] $context Error context
     * @param string $notes Additional information
     *
     * @return boolean Returns if the log entry was handled correctly
     */
    public function logPhpError(
        $level,
        $msg,
        $file,
        $line,
        $context = NULL,
        $notes = NULL
    )
    {
        if (!$this->errorLogActiveStatus[$this->errorCodes[$level]]) {
            return;
        }

        if ($notes == NULL) {
            $notes = $this->notes;
        }

        if ($this->showPhpErrors) {
            $htmlNotes = '';

            if ($notes != NULL) {
                $htmlNotes =
                    "<tr>
                        <td>
                            Notes:
                        </td>
                        <td>
                            $notes
                        </td>
                    </tr>";
            }

            echo
                "<hr>
                <p>
                    <b style=\"font-weight: bold\">[Logger] PHP Error:</b>
                </p>
                <table>
                    <tbody>
                        <tr>
                            <td>
                                Level:
                            </td>
                            <td>
                                " . $this->errorCodes[$level] . " ($level)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Message:
                            </td>
                            <td>
                                $msg
                            </td>
                        </tr>
                        <tr>
                            <td>
                                File:
                            </td>
                            <td>
                                $file
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Line:
                            </td>
                            <td>
                                $line
                            </td>
                        </tr>
                        $htmlNotes
                        <tr>
                            <td>
                                Date:
                            </td>
                            <td>
                                " . date($this->dateFormat, time()) . "
                            </td>
                        </tr>
                    </tbody>
                </table>
                <hr>";
        }

        if ($this->savePhpErrors) {
            $stmt = $this->db->prepare('
                INSERT INTO ' . $this->prefix . 'log' . $this->suffix . '(
                    level,
                    message,
                    file,
                    line,
                    notes
                ) VALUES (
                    :level,
                    :message,
                    :file,
                    :line,
                    :notes
                )
            ');

            $stmt->bindParam(':level', $level);
            $stmt->bindParam(':message', $msg);
            $stmt->bindParam(':file', $file);
            $stmt->bindParam(':line', $line);
            $stmt->bindParam(':notes', $notes);
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
    public function logPhpShutdownError()
    {
        $error = error_get_last();

        if (!empty($error)) {
            $this->logPhpError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line'],
                NULL,
                $this->notes
            );
        }
    }
}
