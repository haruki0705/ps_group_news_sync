# Group News Sync

グループサイトとコーポレートサイト間で更新情報を連携するためのWordPressプラグインです。

## Changelog

### 0.8.1

- Initial release

## コーポレートサイトへのPush同期

公開済みの対象記事を公開・更新・非公開にしたとき、コーポレートサイトへ更新情報をPOSTします。

送信元サイトの `wp-config.php` に、接続先と共有トークンを設定してください。

```php
define(
    'PS_GROUP_NEWS_SYNC_CORPORATE_ENDPOINT',
    'https://コーポレートサイト/wp-json/ps-group-news/v1/import'
);

define(
    'PS_GROUP_NEWS_SYNC_CORPORATE_TOKEN',
    'コーポレートサイトのPS_GROUP_NEWS_IMPORTER_RECEIVE_TOKENと同じ値'
);
```

テストサイトなど、ドメインから送信元を判別できない環境では、サイト識別子も設定します。

```php
define('PS_GROUP_NEWS_SYNC_SITE_ID', 'kodate-plaza');
```
