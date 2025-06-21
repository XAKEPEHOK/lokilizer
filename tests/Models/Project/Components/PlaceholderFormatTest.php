<?php
/**
 * Created for lokilizer
 * Date: 2025-01-23 18:54
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Project\Components;

use PHPUnit\Framework\TestCase;

class PlaceholderFormatTest extends TestCase
{

    public function testMatch()
    {

        $this->assertSame(
            ['world' => '${world}', 'here' => '${here}'],
            PlaceholderFormat::JS->match('Hello ${world} i am ${here} and not {{it}} or {it}')
        );

        $this->assertSame(
            ['world' => '{{world}}', 'here' => '{{here}}'],
            PlaceholderFormat::DOUBLE_CURVE_BRACKETS->match('Hello {{world}} i am {{here}} and not ${it} or {it}')
        );


        $this->assertSame(
            ['world' => '{world}', 'here' => '{here}', 'it' => '{it}'],
            PlaceholderFormat::SINGLE_CURVE_BRACKETS->match('Hello {{world}} i am {{here}} and not ${it} or {it}')
        );

    }
}