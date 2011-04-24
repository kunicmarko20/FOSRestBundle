<?php

namespace FOS\RestBundle\Serializer;

use Symfony\Component\Serializer\Serializer as BaseSerializer,
    Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\DependencyInjection\ContainerAwareInterface;

/*
 * This file is part of the FOSRestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * A service container enabled Serializer that can lazy load normalizers and encoders
 *
 * @author Lukas K. Smith <smith@pooteeweet.org>
 */
class Serializer extends BaseSerializer implements ContainerAwareInterface
{
    protected $container;
    private $encoderFormatMap;
    private $normalizerClassMap;

    /**
     * Set the array maps to enable lazy loading of normalizers and encoders
     *
     * @param array $encoderFormatMap The key is the class name, the value the name of the service
     * @param array $normalizerClassMap The key is the class name, the value the name of the service
     */
    public function __construct(array $encoderFormatMap = null, array $normalizerClassMap = null)
    {
        $this->encoderFormatMap = $encoderFormatMap;
        $this->normalizerClassMap = $normalizerClassMap;
    }

    /**
     * Sets the Container associated with this Controller.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeObject($object, $format, $properties = null)
    {
        try {
            return parent::normalizeObject($object, $format, $properties);
        } catch (\Exception $e) {
            $class = get_class($object);
            if (!$this->lazyLoadNormalizer($class)) {
                throw $e;
            }
        }

        return parent::normalizeObject($object, $format, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalizeObject($data, $class, $format = null)
    {
        try {
            return parent::denormalizeObject($data, $class, $format);
        } catch (\Exception $e) {
            if (!$this->lazyLoadNormalizer($class)) {
                throw $e;
            }
        }

        return parent::denormalizeObject($data, $class, $format);
    }

    /**
     * Lazy load a normalizer for the given class
     *
     * @param string $class A fully qualified class name
     *
     * @return Boolean If the normalizer was successfully lazy loaded
     */
    private function lazyLoadNormalizer($class)
    {
        if (isset($this->normalizerClassMap[$class])
            && $this->container->has($this->normalizerClassMap[$class])
        ) {
            $this->addNormalizer($this->container->get($this->normalizerClassMap[$class]));

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format)
    {
        $this->lazyLoadEncoder($format);

        return parent::encode($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format)
    {
        $this->lazyLoadEncoder($format);

        return parent::decode($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function getEncoder($format)
    {
        $this->lazyLoadEncoder($format);

        return parent::getEncoder($format);
    }

    /**
     * Lazy load an encoder for the given format
     *
     * @param string $format format name
     *
     * @return Boolean If the encoder was successfully lazy loaded
     */
    private function lazyLoadEncoder($format)
    {
        if (!$this->hasEncoder($format)
            && isset($this->encoderFormatMap[$format])
            && $this->container->has($this->encoderFormatMap[$format])
        ) {
            $this->setEncoder($format, $this->container->get($this->encoderFormatMap[$format]));

            return true;
        }

        return false;
    }
}
