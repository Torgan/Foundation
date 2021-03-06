<?php
/*
 * This file is part of the Pomm's Foundation package.
 *
 * (c) 2014 - 2017 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\Foundation\Session;

use PommProject\Foundation\Converter;
use PommProject\Foundation\ParameterHolder;
use PommProject\Foundation\Client\ClientHolder;
use PommProject\Foundation\Converter\ConverterHolder;

/**
 * SessionBuilder
 *
 * Session factory.
 * This class is responsible of creating and configuring a session. It is a
 * default configuration for session and is dedicated to be overloaded.
 *
 * @package   Foundation
 * @copyright 2014 - 2017 Grégoire HUBERT
 * @author    Grégoire HUBERT
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class SessionBuilder
{
    protected $configuration;
    protected $converter_holder;

    /**
     * __construct
     *
     * Instantiate builder.
     *
     * Mandatory configuration options are:
     * dsn:  connection parameters
     * name: database logical name
     *
     * @param array             $configuration
     * @param ConverterHolder   $converter_holder
     */
    public function __construct(array $configuration, ConverterHolder $converter_holder = null)
    {
        $this->configuration = new ParameterHolder(
            array_merge(
                $this->getDefaultConfiguration(),
                $configuration
            )
        );
        $converter_holder = $converter_holder === null
            ? new ConverterHolder
            : $converter_holder
            ;

        $this->initializeConverterHolder($converter_holder);
        $this->converter_holder = $converter_holder;
    }

    /**
     * addParameter
     *
     * Add a configuration parameter.
     *
     * @param  string $name
     * @param  mixed $value
     * @return SessionBuilder $this
     */
    public function addParameter($name, $value)
    {
        $this->configuration->setParameter($name, $value);

        return $this;
    }

    /**
     * getConverterHolder
     *
     * Return the converter holder.
     *
     * @return ConverterHolder
     */
    public function getConverterHolder()
    {
        return $this->converter_holder;
    }

    /**
     * buildSession
     *
     * Build a new session.
     *
     * @final
     * @param  string   $stamp
     * @return Session
     */
    final public function buildSession($stamp = null)
    {
        $this->preConfigure();
        $dsn = $this
            ->configuration->mustHave('dsn')->getParameter('dsn');
        $connection_configuration =
            $this->configuration
            ->mustHave('connection:configuration')
            ->getParameter('connection:configuration')
            ;
        $persist =
            $this->configuration
            ->mustHave('connection:persist')
            ->getParameter('connection:persist')
            ;
        $session = $this->createSession(
            $this->createConnection($dsn, $persist, $connection_configuration),
            $this->createClientHolder(),
            $stamp
        );
        $this->postConfigure($session);

        return $session;
    }

    /**
     * getDefaultConfiguration
     *
     * This must return the default configuration for new sessions. Default
     * parameters are overrided by the configuration passed as parameter to
     * this builder.
     *
     * @return array
     */
    protected function getDefaultConfiguration()
    {
        return
            [
                "connection:configuration" =>
                [
                    'bytea_output'  => 'hex',
                    'intervalstyle' => 'ISO_8601',
                    'datestyle'     => 'ISO',
                    'standard_conforming_strings' => 'true',
                    'timezone'      => date_default_timezone_get(),
                ],
                'connection:persist' => false,
            ];
    }

    /**
     * preConfigure
     *
     * If any computation to the configuration must be done before each session
     * creation, it goes here.
     *
     * @return SessionBuilder $this
     */
    protected function preConfigure()
    {
        return $this;
    }

    /**
     * createConnection
     *
     * Connection instantiation.
     *
     * @param  string   $dsn
     * @param  bool     $persist
     * @param  string|array $connection_configuration
     * @return Connection
     */
    protected function createConnection($dsn, $persist, $connection_configuration)
    {
        return new Connection($dsn, $persist, $connection_configuration);
    }

    /**
     * createSession
     *
     * Session instantiation.
     *
     * @param  Connection   $connection
     * @param  ClientHolder $client_holder
     * @param  string|null  $stamp
     * @return Session
     */
    protected function createSession(Connection $connection, ClientHolder $client_holder, $stamp)
    {
        $session_class = $this->configuration->getParameter('class:session', '\PommProject\Foundation\Session\Session');

        return new $session_class($connection, $client_holder, $stamp);
    }

    /**
     * createClientHolder
     *
     * Instantiate ClientHolder.
     *
     * @return ClientHolder
     */
    protected function createClientHolder()
    {
        return new ClientHolder();
    }

    /**
     * postConfigure
     *
     * Session configuration once created.
     * All pooler registration stuff goes here.
     *
     * @param  Session          $session
     * @return SessionBuilder   $this
     */
    protected function postConfigure(Session $session)
    {
        return $this;
    }

    /**
     * initializeConverterHolder
     *
     * Converter initialization at startup.
     * If new converters are to be registered, it goes here.
     *
     * @param  ConverterHolder  $converter_holder
     * @return SessionBuilder   $this
     */
    protected function initializeConverterHolder(ConverterHolder $converter_holder)
    {
        return $this;
    }
}
