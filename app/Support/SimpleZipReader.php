<?php

declare(strict_types=1);

namespace App\Support;

use ZipArchive;

/**
 * Hosting-proof ZIP reader for XLSX files.
 * Uses native ZipArchive when php-zip is installed, and falls back to a pure-PHP
 * Central Directory + gzinflate reader when php-zip is disabled on shared hosting.
 */
final class SimpleZipReader
{
    /** @var array<string, string> */
    private array $entries = [];

    private bool $opened = false;

    public function open(string $path): bool
    {
        $this->entries = [];
        $this->opened = false;

        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive;
            if ($zip->open($path) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if ($name !== false) {
                        $content = $zip->getFromIndex($i);
                        if ($content !== false) {
                            $this->entries[$name] = $content;
                        }
                    }
                }
                $zip->close();
                $this->opened = true;

                return true;
            }
        }

        return $this->openPurePhp($path);
    }

    public function getFromName(string $name): string|false
    {
        if (! $this->opened) {
            return false;
        }

        $normalized = ltrim($name, '/');

        return $this->entries[$normalized] ?? false;
    }

    /**
     * @return list<string>
     */
    public function entryNames(): array
    {
        return array_keys($this->entries);
    }

    private function openPurePhp(string $path): bool
    {
        $data = @file_get_contents($path);
        if ($data === false || strlen($data) < 22) {
            return false;
        }

        // Locate End of Central Directory (EOCD) signature: PK\x05\x06
        $eocdPos = strrpos($data, "\x50\x4b\x05\x06");
        if ($eocdPos === false) {
            return false;
        }

        $eocd = unpack('vtotal_entries/Vcd_size/Vcd_offset', substr($data, $eocdPos + 10, 10));
        if ($eocd === false) {
            return false;
        }

        $offset = (int) $eocd['cd_offset'];
        $totalEntries = (int) $eocd['total_entries'];
        $dataLen = strlen($data);

        for ($i = 0; $i < $totalEntries; $i++) {
            if ($offset + 46 > $dataLen || substr($data, $offset, 4) !== "\x50\x4b\x01\x02") {
                break;
            }

            $header = unpack(
                'vmethod/Vcrc/Vcomp_size/Vuncomp_size/vname_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Vlocal_offset',
                substr($data, $offset + 10, 36)
            );
            if ($header === false) {
                break;
            }

            $nameLen = (int) $header['name_len'];
            $extraLen = (int) $header['extra_len'];
            $commentLen = (int) $header['comment_len'];
            $name = substr($data, $offset + 46, $nameLen);
            $offset += 46 + $nameLen + $extraLen + $commentLen;

            $localOffset = (int) $header['local_offset'];
            if ($localOffset + 30 > $dataLen || substr($data, $localOffset, 4) !== "\x50\x4b\x03\x04") {
                continue;
            }

            $localHeader = unpack('vname_len/vextra_len', substr($data, $localOffset + 26, 4));
            if ($localHeader === false) {
                continue;
            }

            $contentOffset = $localOffset + 30 + (int) $localHeader['name_len'] + (int) $localHeader['extra_len'];
            $compSize = (int) $header['comp_size'];
            if ($contentOffset + $compSize > $dataLen) {
                continue;
            }

            $compressed = substr($data, $contentOffset, $compSize);
            $method = (int) $header['method'];

            if ($method === 0) {
                $this->entries[ltrim($name, '/')] = $compressed;
            } elseif ($method === 8 && function_exists('gzinflate')) {
                $inflated = @gzinflate($compressed);
                if ($inflated !== false) {
                    $this->entries[ltrim($name, '/')] = $inflated;
                }
            }
        }

        $this->opened = ! empty($this->entries);

        return $this->opened;
    }
}

