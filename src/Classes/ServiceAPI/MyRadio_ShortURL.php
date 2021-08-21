<?php

namespace MyRadio\ServiceAPI;

use MyRadio\Config;
use MyRadio\MyRadio\MyRadioForm;
use MyRadio\MyRadio\MyRadioFormField;
use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadioException;

class MyRadio_ShortURL extends ServiceAPI
{
    private int $short_url_id;
    private string $slug;
    private string $redirect_to;

    public function __construct($data)
    {
        parent::__construct();
        $this->short_url_id = (int)$data['short_url_id'];
        $this->slug = $data['slug'];
        $this->redirect_to = $data['redirect_to'];
    }

    public static function create(string $slug, string $redirectTo): MyRadio_ShortURL
    {
        $sql = 'INSERT INTO public.short_urls (slug, redirect_to) 
                VALUES ($1, $2)
                RETURNING short_url_id';
        $result = self::$db->fetchOne($sql, [$slug, $redirectTo]);

        return self::getInstance($result['short_url_id']);
    }

    public function getID()
    {
        return $this->short_url_id;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return MyRadio_ShortURL
     */
    public function setSlug(string $slug): MyRadio_ShortURL
    {
        self::$db->query(
            'UPDATE public.short_urls
            SET slug = $2
            WHERE short_url_id = $1',
            [$this->short_url_id, $slug]
        );
        $this->slug = $slug;
        $this->updateCacheObject();
        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectTo(): string
    {
        return $this->redirect_to;
    }

    /**
     * @param string $redirectTo
     * @return MyRadio_ShortURL
     */
    public function setRedirectTo(string $redirectTo): MyRadio_ShortURL
    {
        self::$db->query(
            'UPDATE public.short_urls
            SET redirect_to = $2
            WHERE short_url_id = $1',
            [$this->short_url_id, $redirectTo]
        );
        $this->slug = $redirectTo;
        $this->updateCacheObject();
        return $this;
    }

    /**
     * Deletes this short URL.
     */
    public function delete()
    {
        self::$db->query(
            'DELETE FROM public.short_urls
            WHERE short_url_id = $1',
            [$this->short_url_id]
        );
    }

    /**
     * @return MyRadio_ShortURL[]
     */
    public static function getAll()
    {
        $rows = self::$db->fetchColumn(
            'SELECT short_url_id FROM public.short_urls',
            []
        );
        $results = [];
        foreach ($rows as $id) {
            $results[] = self::getInstance($id);
        }
        return $results;
    }

    public function logClick($userAgent, $ipAddress)
    {
        self::$db->query(
            'INSERT INTO public.short_url_clicks (short_url_id, click_time, user_agent, ip_address)
            VALUES ($1, NOW(), $2, $3)',
            [$this->short_url_id, $userAgent, $ipAddress]
        );
    }

    public static function getForm(): MyRadioForm
    {
        $domain = preg_replace('{^//}', '', Config::$website_url);
        return (new MyRadioForm(
            'shorturlfrm',
            'Website',
            'editShortUrl',
            [
                'title' => 'Edit Short URL'
            ]
        ))->addField(
            new MyRadioFormField(
                'slug',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Slug',
                    'explanation' => "The bit that goes after https://$domain. Don't include $domain or a slash."
                ]
            )
        )->addField(
            new MyRadioFormField(
                'redirect_to',
                MyRadioFormField::TYPE_TEXT,
                [
                    'label' => 'Redirect URL',
                    'explanation' => 'Where this short URL will take people.'
                ]
            )
        );
    }

    public function getEditForm(): MyRadioForm
    {
        return self::getForm()->editMode(
            $this->getID(),
            [
                'slug' => $this->getSlug(),
                'redirect_to' => $this->getRedirectTo()
            ]
        );
    }

    protected static function factory($itemid)
    {
        $sql = 'SELECT short_url_id, slug, redirect_to FROM public.short_urls
                WHERE short_url_id = $1 LIMIT 1';
        $result = self::$db->fetchOne($sql, [$itemid]);

        if (empty($result)) {
            throw new MyRadioException('That short URL does not exist.', 404);
        }

        return new self($result);
    }

    public function toDataSource($mixins = [])
    {
        return [
            'short_url_id' => $this->short_url_id,
            'slug' => $this->slug,
            'redirect_to' => $this->redirect_to,
            'edit_link' => [
                'display' => 'icon',
                'value' => 'pencil',
                'title' => 'Click here to edit this short URL',
                'url' => URLUtils::makeURL('Website', 'editShortUrl', ['shorturlid' => $this->getID()]),
            ],
        ];
    }
}
