<?php

namespace Varrcan\Yaturbo;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use DateTime;
use Exception;
use InvalidArgumentException;
use Varrcan\Yaturbo\Orm\YaTurboFeedTable;
use Varrcan\Yaturbo\RssWriter\Feed;
use Varrcan\Yaturbo\RssWriter\GenerateDom;

/**
 * Class GenerateFeed
 * @package Varrcan\Yaturbo
 */
class GenerateFeed
{
    public $itemId;
    public $turboContent;
    public $feed;
    public $iblock;
    public $config;
    public $propertyInfo;

    public function __construct()
    {
        $this->feed   = new Feed();
        $this->iblock = new Iblock();
    }

    /**
     * Сгенерировать XML
     *
     * @param $itemId
     */
    public function generate($itemId)
    {
        $this->config = $this->getItemConfig($itemId);

        if (!$this->config) {
            throw new InvalidArgumentException("Config for element $itemId not found");
        }

        //TODO: Обрамление пользовательских свойств в заданные теги
        //$this->propertyInfo = $this->iblock->getCustomProperty($this->config['iblock_id']);

        $this->feed->initChanel($this->getInitChanelParams());
        $arItem = $this->getElements();

        if ($arItem) {
            $xmlContent = $this->feed->render($arItem);
            $this->saveFile($xmlContent);
        }
    }

    /**
     * Сохранить xml в файл
     *
     * @param $content
     */
    public function saveFile($content)
    {
        if ($content) {
            $dateTime = (new DateTime())->format('YmdHis');
            $fileName = "turbo-$dateTime.rss";

            $this->deleteDirectory();
            $this->createDirectory();

            $file = new File(Application::getDocumentRoot() . Items::$workDir . $this->config['id'] . '/' . $fileName);
            $file->putContents($content);

            YaTurboFeedTable::update($this->config['id'], ['files' => \GuzzleHttp\json_encode([$fileName])]);
        }
    }

    /**
     * Создать директорию с именем ID конфига, если её не существует
     */
    public function createDirectory()
    {
        $dir = new Directory(Application::getDocumentRoot() . Items::$workDir . $this->config['id']);
        $dir->create();
    }

    /**
     * Удалить директорию
     */
    public function deleteDirectory()
    {
        $dir = new Directory(Application::getDocumentRoot() . Items::$workDir . $this->config['id']);
        $dir->delete();
    }

    /**
     * Параметры выгрузки
     *
     * @param $id
     *
     * @return array
     */
    public function getItemConfig($id)
    {
        $item = new Items();

        return $item->getItemConfig($id);
    }

    /**
     * Установить параметры канала
     *
     * @return array
     */
    public function getInitChanelParams():array
    {
        return [
            'title'       => $this->config['feed_name'],
            'link'        => $this->config['site_url'],
            'description' => $this->config['feed_description'],
            'language'    => $this->config['feed_lang'],
            'analytics'   => $this->getAnalytics(),
            'adNetwork'   => $this->config['ad_network'], //TODO
        ];
    }

    /**
     * Получить элементы
     *
     * @return array
     */
    public function getElements()
    {
        $arItem = [];

        try {
            $items = $this->iblock->getIblockItems($this->config);
        } catch (Exception | ArgumentException | ObjectPropertyException | SystemException $e) {
            Debug::writeToFile($e, 'GenerateFeed', 'yandex-turbo.log');
            return $arItem;
        }

        if ($items) {
            foreach ($items as $key => $item) {
                //TODO: Добавить выбор полей автора в настройки
                $author = trim($item['USER']['SECOND_NAME'] . ' ' . $item['USER']['NAME']);

                $arItem[$key] = [
                    '@attributes'    => ['turbo' => 'true'],
                    'link'           => $this->config['site_url'] . $item['DETAIL_PAGE_URL'],
                    'turbo:source'   => '',
                    'turbo:topic'    => '',
                    //'title'          => $item['NAME'], //TODO: Понять, как правильнее, нужен либо H1, либо title
                    'category'       => $item['IBLOCK_SECTION'] ?? '',
                    'pubDate'        => (new DateTime($item[$this->config['pub_date']]))->format(DateTime::RFC822),
                    'author'         => $author ?? '',
                    'turbo:content'  => [
                        '@cdata' => $this->setContent($item),
                    ],
                    'yandex:related' => [
                        '@attributes' => $this->config['link_block_source'] === 'auto' ? ['type' => 'infinity'] : [],
                        '@value'      => $this->getRelated(array_slice($items, 1, $this->config['link_block_count'])),
                    ],
                ];

                $arItem[$key] = array_diff($arItem[$key], ['']);
            }
        }

        return $arItem;
    }

    /**
     * Сформировать контент элемента
     *
     * @param $item
     * @param $fields
     *
     * @return mixed
     */
    public function setContent($item)
    {
        $content = [];

        $contentConfig = $this->config['content_config'];

        uasort($contentConfig, static function ($a, $b) {
            return ($a['block_sort'] > $b['block_sort']);
        });

        foreach ($contentConfig as $key => $param) {
            switch ($param['block_type']) {
                case 'element':
                    $content[] = $item[$param['block_property']];
                    break;
                //TODO: Обрамление пользовательских свойств в заданные теги
                case 'property':
                    $property = $item['PROPERTY_' . $param['block_property'] . '_VALUE'];
                    if (\is_array($property)) {
                        foreach ($property as $value) {
                            $content[] = $value;
                        }
                    } else {
                        $content[] = $property;
                    }
                    break;
            }
        }

        $content = \implode("\n", $content);

        $header   = $this->getHeader($item);
        $share    = $this->getShareBlock();
        $feedback = $this->getFeedbackBlock();

        $this->turboContent = \implode("\n", [$header, $content, $share, $feedback]);

        $this->prepareContent();
        $this->wrapImages();

        return $this->turboContent;
    }

    /**
     * Сформировать header
     *
     * @param $item
     *
     * @return string
     */
    public function getHeader($item):string
    {
        $createHeader['h1'] = $item['NAME'];

        if ($this->config['main_img'] !== 'none') {
            $createHeader['img'] = [
                '@attributes' => [
                    'src' => $this->config['site_url'] . $item[$this->config['main_img']],
                ],
            ];
        }

        $createHeader['menu'] = $this->createMenu();

        $header = (new GenerateDom())->create('header', $createHeader)->saveHTML();

        return $header ?? '';
    }

    /**
     * Сформировать меню
     *
     * @return array
     */
    public function createMenu():array
    {
        $arMenu   = [];
        $elements = [];

        uasort($this->config['menu'], static function ($a, $b) {
            return ($a['block_sort'] > $b['block_sort']);
        });

        foreach ($this->config['menu'] as $menu) {
            $elements[] = [
                '@value'      => $menu['menu_name'],
                '@attributes' => [
                    'href' => $menu['menu_path'],
                ],
            ];
        }

        if ($elements) {
            $arMenu['a'] = $elements;
        }

        return $arMenu;
    }

    /**
     * Блок "Обратная связь"
     *
     * @return string
     */
    public function getFeedbackBlock():string
    {
        $feedbackBlock = $this->config['feedback_block'];

        if ($feedbackBlock && $this->config['show_feedback']) {
            $items = [];
            $dom   = new GenerateDom();

            foreach ($feedbackBlock as $item) {
                $feedbackItems = [
                    '@attributes' => [
                        'data-type' => $item['type'],
                        'data-url'  => $item['value'],
                    ],
                ];

                $feedbackItems['@attributes'] = array_diff($feedbackItems['@attributes'], ['']);

                $items[] = $dom->create('div', $feedbackItems)->saveHTML();
            }

            if ($items) {
                $createItems  = \implode('', $items);
                $feedbackMain = [
                    '@attributes' => [
                        'data-block' => 'widget-feedback',
                        'data-title' => $this->config['feedback_title'],
                        'data-stick' => $this->config['feedback_stick'],
                    ],
                    '@value'      => $createItems,
                ];

                $createFeedback = $dom->create('div', $feedbackMain)->saveHTML();
            }
        }

        return $createFeedback ?? '';
    }

    /**
     * Блок "Поделиться"
     *
     * @return string
     */
    public function getShareBlock():string
    {
        $shareBlock = $this->config['share_block'];

        if ($shareBlock) {
            $shareBlock = array_keys($shareBlock);

            $data = \implode(', ', $shareBlock);

            return "<div data-block=\"share\" data-network=\"$data\"></div>";
        }

        return '';
    }

    /**
     * Веб-аналитика
     *
     * @return array
     */
    public function getAnalytics()
    {
        $row = [];

        $analytics = $this->config['analytics'];

        if ($analytics) {
            foreach ($analytics as $analytic) {
                $row[] = [
                    '@value'      => ' ',
                    '@attributes' => [
                        'type' => $analytic['type'],
                        'id'   => $analytic['value'],
                    ],
                ];
            }
        }

        return $row;
    }

    /**
     * Блок со ссылками на другие страницы
     *
     * @param $items
     *
     * @return string
     */
    public function getRelated($items):string
    {
        $related = [];
        $img     = '';

        foreach ($items as $item) {
            $url = 'url="' . $this->config['site_url'] . $item['DETAIL_PAGE_URL'] . '"';

            if ($this->config['link_block_source'] === 'link' && $item[$this->config['main_img']] !== 'none') {
                $img = 'img="' . $this->config['site_url'] . $item[$this->config['main_img']] . '"';
            }

            $attributes = trim($url . ' ' . $img);

            $value     = $item['NAME'];
            $related[] = "<link $attributes>$value</link>";
        }

        if ($related) {
            return \implode("\n", $related);
        }

        return '';
    }

    /**
     * Очистить содержимое
     *
     * @return $this
     */
    public function prepareContent()
    {
        $this->turboContent = htmlspecialchars_decode($this->turboContent);
        $this->turboContent = $this->stripAllTags($this->turboContent, $this->allowedTags());

        $this->turboContent = preg_replace('/<p[^>]*?>/', '<p>', $this->turboContent);
        $this->turboContent = preg_replace('/<ul[^>]*?>/', '<ul>', $this->turboContent);
        $this->turboContent = preg_replace('/<ol[^>]*?>/', '<ol>', $this->turboContent);
        $this->turboContent = preg_replace('/<li[^>]*?>/', '<li>', $this->turboContent);
        $this->turboContent = preg_replace('/<table[^>]*?>/', '<table>', $this->turboContent);

        return $this;
    }

    /**
     * Обернуть изображения в тег figure
     *
     * @return $this
     */
    public function wrapImages()
    {
        preg_match_all('!(<img.*>)!Ui', $this->turboContent, $matches);

        if (isset($matches[1]) && !empty($matches)) {
            foreach ($matches[1] as $value) {
                $this->turboContent = str_replace($value, "<figure>{$value}</figure>", $this->turboContent);
            }
        }

        return $this;
    }

    /**
     * Удалить все теги из строки
     *
     * @param      $string
     * @param null $allowable_tags
     *
     * @return string|string[]|null
     */
    public function stripAllTags($string, $allowable_tags = null)
    {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string, implode(',', $allowable_tags));

        return $string;
    }

    /**
     * Разрешенные теги
     *
     * @return array
     */
    protected function allowedTags()
    {
        /** @noinspection HtmlRequiredAltAttribute */
        /** @noinspection RequiredAttributes */
        /** @noinspection HtmlDeprecatedTag */
        return [
            '<header>',
            '<h1>',
            '<h2>',
            '<h3>',
            '<h4>',
            '<h5>',
            '<h6>',
            '<p>',
            '<br>',
            '<ul>',
            '<ol>',
            '<li>',
            '<b>',
            '<strong>',
            '<i>',
            '<em>',
            '<sup>',
            '<sub>',
            '<ins>',
            '<del>',
            '<small>',
            '<big>',
            '<pre>',
            '<abbr>',
            '<u>',
            '<a>',
            '<figure>',
            '<img>',
            '<figcaption>',
            '<video>',
            '<source>',
            '<iframe>',
            '<blockquote>',
            '<table>',
            '<tr>',
            '<th>',
            '<td>',
            '<menu>',
            '<hr>',
            '<div>',
            '<code>',
            '<dl>',
            '<dt>',
            '<dd>',
        ];
    }
}
