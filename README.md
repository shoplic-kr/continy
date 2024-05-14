# Continy

아주 작고 간단한 워드프레스 플러그인과 테마 개발을 위한 컨테이너. 나름 의존성 주입을 지원한답니다!

## 요구사항

* PHP 8.0 이상

## 설치하기

워드프레스 플러그인에서 composer 패키지 매니저를 사용하여 의존정 패키지로 설치합니다.

```bash
composer require 
```

## 시작하기

autoload.php 파일은 정확하게 덤프되었나요?

```shell
composer dump-autoload
composer dump-autoload -a # 최적화 버전
```

그다음, 아래처럼 `Shoplic\Continy\bootstrap()` 함수를 실행하면 됩니다.

```php
<?php
/**
 * Plugin Name: My plugin
 * Description: ...
 * ... 
 */

require_once __DIR__ . './vendor/autoload.php';

Shoplic\Continy\bootstrap();
```
