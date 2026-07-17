# rasuvaeff/yii3-feature-flags-ui
[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/yii3-feature-flags-ui/v)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-feature-flags-ui/downloads)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![Build](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/yii3-feature-flags-ui/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-feature-flags-ui/php)](https://packagist.org/packages/rasuvaeff/yii3-feature-flags-ui)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
Панель пользовательского интерфейса администратора для управления флагами функций Yii3.

 > Используете помощника по программированию с искусственным интеллектом? [llms.txt](llms.txt) содержит компактную ссылку на API, которой вы можете поделиться с моделью. @@ЛИНИЯ@@
A drop-in admin panel for [`rasuvaeff/yii3-feature-flags`](https://github.com/rasuvaeff/yii3-feature-flags):
перечислите флаги в сортируемой сетке (с переключателем уничтожения и отключенными значками), создайте
 и отредактируйте их (имя, включено, развертывание, соль, переключатель уничтожения, среды), удалите
 их и создайте события `FlagChanged` для контрольного журнала / аннулирования кэша. Поставщики
, доступные только для чтения, автоматически отключают элементы управления. @@ЛИНИЯ@@
## Требования
- PHP 8.3+
 - `rasuvaeff/yii3-feature-flags` ^1.0 - `Flag`, `FlagProvider`, `WritableFlagProvider`
 - Доступный для записи бэкенд провайдера (обычно `rasuvaeff/yii3-feature-flags-db` ^1.0), привязанный к `FlagProvider` и `WritableFlagProvider`
 - `yiisoft/yii-view-renderer`, `yiisoft/router`, `yiisoft/user`
 - `yiisoft/html`, `yiisoft/validator`, `yiisoft/form-model`, `yiisoft/data`, `yiisoft/yii-dataview`
 - Конкретная реализация маршрутизатора (например, `yiisoft/router-fastroute`) - предоставляется вашим приложением
 — CSS Bootstrap 5, загружаемый хост-приложением (представления используют классы Bootstrap, без встроенных стилей)

 Сетка списка отображается на стороне сервера из контейнера внедрения внедрения приложения
 («FlagsGridFactory»), поэтому хосту **нет** необходимости загружать WidgetFactory`. @@ЛИНИЯ@@
## Установка
```bash
composer require rasuvaeff/yii3-feature-flags-ui
```
## Использование
В пакет входит подключение конфигурационного плагина Yii3 (`di`, `params`). Добавьте свои параметры
 в цепочку слияния:

```php
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;

return [
    FlagRoutes::PARAM_KEY => [
        'route_prefix' => '/admin/flags',
        'layout' => null,
        'views' => [
            'list' => '/abs/path/to/flags-list.php',
            'edit' => '/abs/path/to/flags-edit.php',
        ],
        'middlewares' => [
            'all' => [AuthMiddleware::class],
        ],
        // RequestBodyParser is added automatically to POST routes (store, update, delete).
        // Set 'body_parser' => false if your pipeline already applies it globally.
    ],
];
```
`layout` управляет общей оболочкой. «views.list» и «views.edit» переопределяют только соответствующие шаблоны; они не заменяют макет.

 Привяжите флаговые контракты к своему провайдеру. С `rasuvaeff/yii3-feature-flags-db` ^1.0
 это происходит автоматически — его конфигурационный плагин связывает `WritableFlagProvider` и `FlagProvider`
 с одним и тем же `DbFlagProvider`. Для пользовательского бэкэнда свяжите их самостоятельно:

```php
use Rasuvaeff\Yii3FeatureFlags\{FlagProvider, WritableFlagProvider};
use Rasuvaeff\Yii3FeatureFlagsDb\DbFlagProvider;

return [
    FlagProvider::class => DbFlagProvider::class,
    WritableFlagProvider::class => Reference::to(FlagProvider::class),
];
```
## Маршруты
| Метод | Путь | Действие | Имя по умолчанию |
 |---|---|---|---|
 | ПОЛУЧИТЬ | `{префикс}` | `Yii\Список\Действие` | `флаги/список` |
 | ПОЛУЧИТЬ | `{префикс}/новый` | `Yii\Edit\Action::new()` | `флаги/создать` |
 | ПОЛУЧИТЬ | `{префикс}/{имя}/редактировать` | `Yii\Редактировать\Действие` | `флаги/редактировать` |
 | ПОСТ | `{префикс}/новый` | `Yii\Update\Action::new()` | `флаги/магазин` |
 | ПОСТ | `{префикс}/{имя}` | `Yii\Обновление\Действие` | `флаги/обновление` |
 | ПОСТ | `{префикс}/{имя}/удалить` | `Yii\Удалить\Действие` | `флаги/удалить` |

 `middlewares.{all,list,edit,create,store,update,delete}` — добавьте промежуточное программное обеспечение для каждого слота, не разветвляя маршруты. `RequestBodyParser` автоматически добавляется в маршруты POST (сохранение, обновление, удаление); установите `'body_parser' => false` в параметрах, чтобы отказаться.

 URL-адреса и перенаправления генерируются через маршрутизатор («UrlGeneratorInterface») по имени маршрута; ссылки остаются корректными под любым префиксом или поддоменом. Переопределите `route_names` в параметрах, если ваше приложение использует другое соглашение об именах. @@ЛИНИЯ@@
### Плоская проводка
Подключите связанный `config/routes.php` явно в `configuration.php`:

```php
'routes' => 'vendor/rasuvaeff/yii3-feature-flags-ui/config/routes.php',
```
Префикс маршрута, имена и промежуточное ПО считываются из параметров (`FlagRoutes::PARAM_KEY`). @@ЛИНИЯ@@
### Групповая админ-панель
Внутри группы (типичный подход для административной области с общим префиксом):

```php
use Rasuvaeff\Yii3FeatureFlagsUi\FlagRoutes;
use Yiisoft\Router\Group;

Group::create(prefix: '/admin')->routes(
    ...FlagRoutes::fromParams($params),
);
```
`fromParams()` считывает префикс, имена, промежуточное программное обеспечение и отказ от парсера тела из
 `$params[FlagRoutes::PARAM_KEY]`, поэтому регистрация маршрута и генерация URL-адреса `FlagUrls`
 всегда синхронизируются.

 Для полного контроля над именами используйте create() напрямую и добавьте соответствующие `route_names` к параметрам:

```php
FlagRoutes::create(
    prefix: '/flags',
    names: ['list' => 'admin/flags', 'edit' => 'admin/flags/edit'],
    middlewares: ['all' => [AuthMiddleware::class]],
)
```
## Авторизация
Пакет не обеспечивает внутренний контроль доступа. Защитите маршруты с помощью
 `middlewares.all` (или ключей для каждого маршрута). Пакет обеспечивает внедрение CurrentUser
 только для событий аудита. @@ЛИНИЯ@@
## Публичный API
| Класс | Описание |
 |---|---|
 | `FlagRoutes` | Строит 6 маршрутов; `fromParams($params)` для групповых панелей,`create()` для полного управления |
 | `Yii\Список\Действие` | GET сетка всех флагов со значками KILLED/OFF |
 | `Yii\Редактировать\Действие` | GET форма редактирования существующего флага; `::new()` для создания формы |
 | `Yii\Обновление\Действие` | POST-проверка + сохранение; `::new()` для создания; повторный рендеринг на недействительный |
 | `Yii\Удалить\Действие` | POST удалить флаг -> перенаправить в список |
 | `Форма\ФлагФорм` | Отправленные данные для редактирования (имя, включено, развертывание, соль, killSwitch, среда) |
 | `Проверка\FlagFormNormalizer` | Приводит проверенную форму к флагу |
 | `Рендерер\TemplateRendererInterface` | Рендеринг шва (тестируемые действия) |
 | `Рендерер\ViewTemplateRenderer` | Средство рендеринга по умолчанию через `WebViewRenderer` |
 | `Событие\FlagChanged` | Событие PSR-14 после сохранения/удаления (имя, операция, актер) | @@ЛИНИЯ@@
## Поставщики только для чтения
Если ваш FlagProvider не реализует WritableFlagProvider:

 - `config/routes.php` по-прежнему регистрирует все маршруты; Проверки `instanceof` во время выполнения возвращают 403 при POST.
 — в представлении списка отображается значок «Поставщик только для чтения», а кнопка «Создать» скрыта.
 — в режиме редактирования отключаются все поля и отображается предупреждение.

 Это позволяет просматривать ConfigFlagProvider (флаги только для конфигурации) в пользовательском интерфейсе без поддержки записи. @@ЛИНИЯ@@
## Безопасность
| Концерн | Поведение |
 |---|---|
 | Поставщики только для чтения | `Обновление`/`Удалить` отклонено с помощью HTTP 403 |
 | Неизвестное название флага | `Edit` возвращает 404, `Update`/`Delete` возвращает 404 |
 | Неверный ввод | Повторно отображает страницу редактирования с помощью HTTP 200, без записи |
 | Вставка имени флага | При существующем редактировании отправленное `имя` игнорируется; название маршрута закреплено |
 | Предупреждение об аварийном выключателе | Форма редактирования всегда отображает предупреждение; пользователи не могут отключить предупреждение |
 | CSRF | Применяется промежуточным программным обеспечением вашего приложения; форма выдает скрытое поле `_csrf`, когда присутствует атрибут запроса `csrf_token` |
 | Выход | Все значения проходят через `Yiisoft\Html\Html::encode()` / виджеты Html / кодировку GridView | @@ЛИНИЯ@@
## Настройка представлений
Переопределите в параметрах `views.list` и/или `views.edit` абсолютные пути к вашим собственным шаблонам. Шаблоны получают те же переменные, что и встроенные — см. «resources/views/».

 В форме редактирования используются имена входных данных, относящиеся к `Flag[...]` (например, `Flag[name]`, `Flag[enabled]`). Пользовательские шаблоны редактирования должны сохранять эту область действия, чтобы FlagForm::fromParsedBody() работал.

 **Flash-сообщения** не встроены — пакет не знает о сеансе хост-приложения. Подпишитесь на «FlagChanged» в своем приложении, чтобы добавлять флэш-уведомления, аннулирование кеша или записи контрольного журнала. @@ЛИНИЯ@@
## Почему существует «FlagChanged»
Пакет выдает `FlagChanged` после сохранения/удаления, чтобы ведущее приложение могло реагировать без
 связывания себя с действиями пользовательского интерфейса. Типичное использование — аннулирование кэша, ведение журнала аудита
, счетчики метрик и зависимая реконфигурация. Поле `actor`
 содержит текущий идентификатор пользователя; `null` для гостей.
