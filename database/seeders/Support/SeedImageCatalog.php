<?php

namespace Database\Seeders\Support;

/**
 * Curated Unsplash photography used for seeded storefront data.
 *
 * Every photo ID below was checked to resolve on images.unsplash.com and reviewed for
 * subject matter. `php artisan app:fetch-seed-images` downloads them once to the public
 * disk, and seeders reference the local copies, so seeded data never depends on Unsplash
 * at render time. Photos are free to use under the Unsplash License.
 */
final class SeedImageCatalog
{
    /** Directory on the public disk that holds downloaded seed images. */
    public const DIRECTORY = 'seed';

    /**
     * Product photo IDs per catalogue domain (see ProductSeeder::resolveDomainKey()).
     *
     * @var array<string, list<string>>
     */
    public const PRODUCTS = [
        'perfumes' => [
            '1523293182086-7651a899d37f',
            '1541643600914-78b084683601',
            '1557170334-a9632e77c6e4',
            '1563170351-be82bc888aa4',
            '1588405748880-12d1d2a59f75',
            '1592945403244-b3fbafd7f539',
            '1594035910387-fea47794261f',
            '1595425970377-c9703cf48b6d',
            '1600612253971-422e7f7faeb6',
            '1610461888750-10bfc601b874',
            '1615634260167-c8cdede054de',
            '1616949755610-8c9bbc08f138',
            '1619994403073-2cec844b8e63',
        ],
        'makeup' => [
            '1487412947147-5cebf100ffc2',
            '1503236823255-94609f598e71',
            '1512496015851-a90fb38ba796',
            '1515688594390-b649af70d282',
            '1522335789203-aabd1fc54bc9',
            '1522338242992-e1a54906a8da',
            '1586495777744-4413f21062fa',
            '1596462502278-27bfdc403348',
            '1596704017254-9b121068fb31',
            '1598452963314-b09f397a5c48',
            '1616683693504-3ea7e9ad6fec',
            '1625093742435-6fa192b6fb10',
            '1631214540553-ff044a3ff1d4',
            '1519014816548-bf5fe059798b',
            '1522337660859-02fbefca4702',
            '1599948128020-9a44505b0d1b',
            '1604654894610-df63bc536371',
        ],
        'skincare' => [
            '1556228578-8c89e6adf883',
            '1556228720-195a672e8a03',
            '1556229010-6c3f2c9ca5f8',
            '1567721913486-6585f069b332',
            '1571781926291-c477ebfd024b',
            '1576426863848-c21f53c60b19',
            '1580870069867-74c57ee1bb07',
            '1585945037805-5fd82c2e60b1',
            '1598440947619-2c35fc9aa908',
            '1599305090598-fe179d501227',
            '1601049541289-9b1b7bbbfe19',
            '1601049676869-702ea24cfd58',
            '1607602132700-068258431c6c',
            '1608571423902-eed4a5ad8108',
            '1612817288484-6f916006741a',
            '1615396899839-c99c121888b0',
            '1617897903246-719242758050',
            '1619451334792-150fd785ee74',
            '1625753783470-ec2ab4efeeec',
            '1629198688000-71f23e745b6e',
            '1631729371254-42c2892f0e6e',
        ],
        'hair' => [
            '1519735777090-ec97162dc266',
            '1522337360788-8b13dee7a37e',
            '1535585209827-a15fcdbc4c2d',
            '1560869713-7d0a29430803',
            '1597354984706-fac992d9306f',
            '1608248543803-ba4f8c70ae0b',
            '1608248597279-f99d160bfcbc',
            '1621607512214-68297480165e',
            '1622286342621-4bd786c2447c',
        ],
        'body-care' => [
            '1526947425960-945c6e72858f',
            '1597931752949-98c74b5b159f',
            '1600857544200-b2f666a9a2ec',
            '1607006344380-b6775a0824a7',
            '1625772452859-1c03d5bf1137',
            '1620916566398-39f1143ab7be',
            '1620917669809-1af0497965de',
            '1610992015732-2449b76344bc',
            '1616394584738-fc6e612e71b9',
            '1570172619644-dfd03ed5d881',
            '1599817878414-43ef36677cf0',
            '1624454002302-36b824d7bd0a',
        ],
        'men' => [
            '1590159763121-7c9fd312190d',
            '1593702295094-aea22597af65',
            '1599351431202-1e0f0137899a',
            '1601612628452-9e99ced43524',
            '1621607512022-6aecc4fed814',
        ],
        'accessories' => [
            '1516975080664-ed2fc6a32937',
            '1527799820374-dcf8d9d4a388',
            '1600428877878-1a0fd85beda8',
            '1612817159949-195b6eb9e31a',
            '1626784215021-2e39ccf971cd',
            '1608979048467-6194dabc6a3d',
        ],
        'health-care' => [
            '1471864190281-a93a3070b6de',
            '1512069772995-ec65ed45afd6',
            '1550572017-edd951b55104',
            '1584308666744-24d5c474f2ae',
            '1607619056574-7b8d3ee536b2',
            '1628771065518-0d82f1938462',
            '1544367567-0f2fcb009e0b',
        ],
        'gifts' => [
            '1512909006721-3d6018887383',
            '1513885535751-8b9238bd345a',
            '1549465220-1a8b9238cd48',
            '1607344645866-009c320b63e0',
            '1608755728617-aefab37d2edd',
            '1583209814683-c023dd293cc6',
        ],
    ];

    /**
     * Wide hero photography, keyed by the slide it illustrates.
     *
     * @var array<string, string>
     */
    public const SLIDES = [
        'skincare' => '1598440947619-2c35fc9aa908',
        'favourites' => '1617897903246-719242758050',
        'perfumes' => '1592945403244-b3fbafd7f539',
        'makeup' => '1596462502278-27bfdc403348',
    ];

    /** Public-disk path of a domain's product photo. */
    public static function productPath(string $domain, int $index): string
    {
        return sprintf('%s/products/%s-%02d.jpg', self::DIRECTORY, $domain, $index + 1);
    }

    /** Public-disk path of a hero slide photo. */
    public static function slidePath(string $slide): string
    {
        return sprintf('%s/slider/%s.jpg', self::DIRECTORY, $slide);
    }

    /**
     * Every file to download: its public-disk path and its source URL.
     *
     * @return list<array{path: string, url: string}>
     */
    public static function downloads(): array
    {
        $downloads = [];

        foreach (self::PRODUCTS as $domain => $photoIds) {
            foreach ($photoIds as $index => $photoId) {
                $downloads[] = [
                    'path' => self::productPath($domain, $index),
                    'url' => self::url($photoId, width: 800, height: 800),
                ];
            }
        }

        foreach (self::SLIDES as $slide => $photoId) {
            $downloads[] = [
                'path' => self::slidePath($slide),
                'url' => self::url($photoId, width: 1920, height: 1080),
            ];
        }

        return $downloads;
    }

    private static function url(string $photoId, int $width, int $height): string
    {
        return "https://images.unsplash.com/photo-{$photoId}?auto=format&fit=crop&fm=jpg&q=80&w={$width}&h={$height}";
    }
}
