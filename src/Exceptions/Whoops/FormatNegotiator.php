<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Exceptions\Whoops;

use InvalidArgumentException;
use Negotiation\Accept;
use Negotiation\Negotiator;
use Psr\Http\Message\ServerRequestInterface;

use Throwable;

use function array_key_exists;
use function count;

class FormatNegotiator
{
    /**
     * Available format handlers
     *
     * @var Formats\Format[]
     */
    protected array $formats;

    /**
     * Default format
     *
     * Formats\Format
     */
    private Formats\Format $defaultFormat;

    /**
     * @var array<string, Formats\Format>
     */
    protected array $formatMimesMap = [];

    /**
     * @var string[]
     */
    protected array $mimePriorities = [];

    protected Negotiator $negotiator;

    /**
     * FormatNegotiator constructor.
     *
     * @param Formats\Format[] $formats
     * @param Formats\Format $defaultFormat
     */
    public function __construct(array $formats, Formats\Format $defaultFormat)
    {
        foreach ($formats as $format) {
            if (!$format instanceof Formats\Format) {
                throw new InvalidArgumentException('Format must be an instanceof Format interface');
            }

            $this->addFormatMimes($format);
        }

        $this->formats = $formats;

        $this->defaultFormat = $defaultFormat;
        $this->addFormatMimes($defaultFormat);

        $this->negotiator = new Negotiator();
    }

    protected function addFormatMimes(Formats\Format $format): void
    {
        foreach ($format->getMimes() as $mime) {
            if (!array_key_exists($mime, $this->formatMimesMap)) {
                $this->formatMimesMap[$mime] = $format;
                $this->mimePriorities[] = $mime;
            }
        }
    }

    /**
     * Returns the preferred format based on the Accept header
     *
     * @param ServerRequestInterface $request
     * @return Formats\Format
     */
    public function negotiate(ServerRequestInterface $request): Formats\Format
    {
        $acceptTypes = $request->getHeader('accept');
        if (count($acceptTypes) === 0) {
            return $this->defaultFormat;
        }

        try {
            $format = $this->negotiator->getBest($acceptTypes[0], $this->mimePriorities);
        } catch (Throwable $e) {
            return $this->defaultFormat;
        }

        if ($format === null) {
            return $this->defaultFormat;
        }

        /** @var Accept $format */
        return $this->formatMimesMap[$format->getType()];
    }
}
