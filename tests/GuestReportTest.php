<?php

namespace SpanishGuestReportGenerator;

use \PHPUnit\Framework\TestCase;

use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamWrapper;
use \org\bovigo\vfs\vfsStreamDirectory;

/**
 * @coversDefaultClass \SpanishGuestReportGenerator\GuestReport
 */
class GuestReportTest extends TestCase
{
    private $gr;

    private $hf;
    private $gf;
    private $dt;

    private $guests = [
        [
            'isSpanish'    => true,
            'idNumber'     => '00126939278',
            'docType'      => 'd',
            'docIssueDate' => '2013-02-16',
            'lastName1'    => 'Eutychus',
            'lastName2'    => 'Tarik',
            'firstName'    => 'Orlov',
            'gender'       => 'm',
            'birthDate'    => '1972-00-00',
            'countryName'  => 'España',
            'arrivalDate'  => '2016-09-26',
        ],
        [
            'isSpanish'    => false,
            'idNumber'     => '82021854627922',
            'docType'      => 'p',
            'docIssueDate' => '2011-11-24',
            'lastName1'    => 'Munroe',
            'lastName2'    => '',
            'firstName'    => 'Amor Asim',
            'gender'       => 'f',
            'birthDate'    => '1972-04-28',
            'countryName'  => 'Holanda',
            'arrivalDate'  => '2016-09-26',
        ],
    ];

    private $hotels = [
        [
            'hotelCode'      => '12345ABCDE',
            'hotelName'      => 'Green Phone Hotel',
            'reportDateTime' => null,
            'guests'         => null,
        ],
        [
            'hotelCode'      => 'ABCDE12345',
            'hotelName'      => 'Transparent Scarf Hotel',
            'reportDateTime' => null,
            'guests'         => null,
        ]
    ];

    private $chainCode    = 'ZYXWV98765';
    private $chainName    = 'Simple Tiger Hotels';
    private $reportNumber = 321;

    private $expectedContents;

    private $rootPath      = 'root';
    private $directoryPath = 'this/is/a/test/path';
    private $filename;

    public function setUp()
    {
        $this->hf = new HotelFactory;
        $this->gf = new GuestFactory;
        $this->dt = new \DateTime('2016-09-26 00:00:00');
        $this->gr = new GuestReport($this->hf, $this->gf, $this->dt);

        foreach ($this->hotels as &$h) {
            $h['reportDateTime'] = $this->dt;
            $h['guests'] = $this->gf->buildMultiple($this->guests);
        }

        $this->filename = $this->chainCode.'.'.$this->reportNumber;
        if (file_exists($this->filename)) {
            die("File `{$this->filename}` not found in tests directory.");
        }
        $this->expectedContents = file_get_contents(__DIR__.'/'.$this->filename);

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory($this->rootPath));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(GuestReport::class, new GuestReport($this->hf, $this->gf, $this->dt));
        $this->assertInstanceOf(GuestReport::class, new GuestReport($this->hf, $this->gf));
    }

    public function chainCodeProvider()
    {
        return [
            ['', ''],
            [null, ''],
            [1234567890, '1234567890'],
            ['ABCDEFGHIJ', 'ABCDEFGHIJ'],
            ['ABCDE12345', 'ABCDE12345'],
            ['ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'ABCDEFGHIJ'],
            ['str with spaces', 'str with s'],
        ];
    }

    /**
     * @covers          ::setChainCode
     * @dataProvider    chainCodeProvider
     */
    public function testSetChainCode($actual, $expected)
    {
        $this->gr->setChainCode($actual);

        $rc = new \ReflectionClass($this->gr);
        $rp = $rc->getProperty('chainCode');
        $rp->setAccessible(true);

        $this->assertEquals($expected, $rp->getValue($this->gr));
    }

    public function chainNameProvider()
    {
        return [
            ['', ''],
            [null, ''],
            [1234567890, '1234567890'],
            ['ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
            ['ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ABCD'],
            ['Lorem ipsum dolor sit amet, consectetur adipiscing elit', 'Lorem ipsum dolor sit amet, consectetur '],
        ];
    }

    /**
     * @covers          ::setChainName
     * @dataProvider    chainNameProvider
     */
    public function testSetChainName($actual, $expected)
    {
        $this->gr->setChainName($actual);

        $rc = new \ReflectionClass($this->gr);
        $rp = $rc->getProperty('chainName');
        $rp->setAccessible(true);

        $this->assertEquals($expected, $rp->getValue($this->gr));
    }

    public function reportNumberProvider()
    {
        return [
            ['',         '001'],
            [null,       '001'],
            [true,       '001'],
            [false,      '001'],
            [0,          '001'],
            [-10000,     '001'],
            [10000,      '001'],
            [321,        '321'],
            [123.456,    '123'],
            [-123.456,   '001'],
            [0123,       '083'],
            [0x1A,       '026'],
            [0b11111111, '255'],
            ['321',      '321'],
            ['abcde',    '001'],
            [new \stdClass, '001'],
        ];
    }

    /**
     * @covers          ::setReportNumber
     * @dataProvider    reportNumberProvider
     */
    public function testSetReportNumber($actual, $expected)
    {
        $this->gr->setReportNumber($actual);

        $rc = new \ReflectionClass($this->gr);
        $rp = $rc->getProperty('reportNumber');
        $rp->setAccessible(true);

        $this->assertTrue($expected === $rp->getValue($this->gr));
    }

    public function reportDateTimeProvider()
    {
        return [
            [new \DateTime('2013-07-21 12:34')],
            [new \DateTime('2015-08-22 11:00')],
            [new \DateTime('2016-09-23 09:09')],
            [new \DateTime('2017-10-24 17:18')],
            [new \DateTime('2018-11-25 00:00')],
        ];
    }

    /**
     * @covers          ::setReportDateTime
     * @dataProvider    reportDateTimeProvider
     */
    public function testSetReportDateTime($dt)
    {
        $this->gr->setReportDateTime($dt);

        $rc = new \ReflectionClass($this->gr);
        $rp = $rc->getProperty('reportDateTime');
        $rp->setAccessible(true);

        $this->assertTrue($dt === $rp->getValue($this->gr));
    }

    /**
     * @covers ::setChain
     */
    public function testSetChain()
    {
        $this->assertInstanceOf(
            GuestReport::class,
            $this->gr->setChain(
                'ABCDE12345',
                'Lorem ipsum dolor sit amet',
                $this->dt,
                $this->hotels
            )
        );
    }

    /**
     * @covers ::setHotels
     */
    public function testSetHotels()
    {
        $this->assertInstanceOf(GuestReport::class, $this->gr->setHotels($this->hotels));
    }

    public function hotelProvider()
    {
        $this->setUp();

        $data = [];
        foreach ($this->hotels as $actual) {
            $expected = $this->hf->build($actual);
            array_push($data, [$actual, $expected]);
        }

        return $data;
    }

    /**
     * @covers          ::setHotel
     * @dataProvider    hotelProvider
     */
    public function testSetHotel($actual, $expected)
    {
        $hotelCode      = '';
        $hotelName      = '';
        $reportDateTime = new \DateTime;
        $guests         = [];
        extract($actual);

        $this->gr->setHotel($hotelCode, $hotelName, $reportDateTime, $guests);

        $rc = new \ReflectionClass($this->gr);
        $rp = $rc->getProperty('hotels');
        $rp->setAccessible(true);

        $hotels = $rp->getValue($this->gr);

        $this->assertInstanceOf(Hotel::class, $hotels[$hotelCode]);
        $this->assertEquals($expected, $hotels[$hotelCode]);
    }

    /**
     * @covers          ::setHotelGuests
     * @dataProvider    hotelProvider
     */
    public function testSetHotelGuests($actual, $expected)
    {
        $hotelCode      = '';
        $hotelName      = '';
        $reportDateTime = new \DateTime;
        $guests         = [];
        extract($actual);

        $this->gr->setHotel($hotelCode, $hotelName, $reportDateTime, $guests);

        $this->assertInstanceOf(GuestReport::class, $this->gr->setHotelGuests($hotelCode, $guests));
    }

    /**
     * @covers              ::setHotelGuests
     * @expectedException   \SpanishGuestReportGenerator\GuestReportException
     */
    public function testSetHotelGuestsRaisesException()
    {
        $this->gr->setHotelGuests('whatevs', []);
    }

    /**
     * @covers ::setDirectoryPath
     */
    public function testSetDirectoryPath()
    {
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild($this->directoryPath));

        $result = $this->gr->setDirectoryPath(vfsStream::url($this->rootPath.'/'.$this->directoryPath));

        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($this->directoryPath));

        $this->assertInstanceOf(GuestReport::class, $result);
    }

    /**
     * @covers              ::setDirectoryPath
     * @expectedException   \SpanishGuestReportGenerator\GuestReportException
     */
    public function testSetDirectoryPathDontCreate()
    {
        $this->gr->setDirectoryPath(vfsStream::url($this->rootPath.'/'.$this->directoryPath), false);
    }

    /**
     * @covers ::save
     */
    public function testSave()
    {
        $basePath = $this->rootPath.'/'.$this->directoryPath;

        $this->gr
        ->setDirectoryPath(vfsStream::url($basePath))
        ->setReportNumber($this->reportNumber)
        ->setChain(
            $this->chainCode,
            $this->chainName,
            $this->dt,
            $this->hotels
        )
        ->save();

        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild($this->directoryPath.'/'.$this->filename));

        $actualContents = file_get_contents(vfsStream::url($basePath.'/'.$this->filename));
        $this->assertEquals($this->expectedContents, $actualContents);
    }

    /**
     * @covers ::save
     * @expectedException   \SpanishGuestReportGenerator\GuestReportException
     */
    public function testSaveDontOverwrite()
    {
        $this->gr->setDirectoryPath(vfsStream::url($this->rootPath.'/'.$this->directoryPath));

        vfsStream::newFile($this->directoryPath.'/'.$this->filename, 0644)
        ->at(vfsStreamWrapper::getRoot());

        $this->gr->setReportNumber($this->reportNumber)
        ->setChain(
            $this->chainCode,
            $this->chainName,
            $this->dt,
            $this->hotels
        )
        ->save(false);
    }

    /**
     * @covers ::getFilename
     */
    public function testGetFilename()
    {
        // Nothing yet set
        $this->assertEquals('EMPTY.999', $this->gr->getFilename());

        // Only report number set
        $this->gr->setReportNumber($this->reportNumber);
        $this->assertEquals(
            'EMPTY.'.$this->reportNumber,
            $this->gr->getFilename()
        );

        // Single Hotel set
        $this->gr->setHotel(
            $this->hotels[0]['hotelCode'],
            $this->hotels[0]['hotelName']
        );
        $this->assertEquals(
            $this->hotels[0]['hotelCode'].'.'.$this->reportNumber,
            $this->gr->getFilename()
        );

        // Multiple hotels set but no Chain code
        $this->gr->setHotels($this->hotels);
        $this->assertEquals(
            'NONAME.'.$this->reportNumber,
            $this->gr->getFilename()
        );

        // Multiple hotels with Chain info set
        $this->gr
        ->setChainCode($this->chainCode)
        ->setChainName($this->chainName);
        $this->assertEquals(
            $this->chainCode.'.'.$this->reportNumber,
            $this->gr->getFilename()
        );
    }
}

/**
 * Override realpath() in current namespace for testing
 *
 * @param string $path  the file path
 *
 * @return string
 *
 * @codeCoverageIgnore
 */
function realpath($path)
{
    return $path;
}
