# Continy

아주 작고 간단한 워드프레스 플러그인과 테마 개발을 위한 컨테이너. 나름 의존성 주입을 지원한답니다!

## Table of Contents
* 요구사항
* 설치하기
* 시작하기
* 모듈 설정하기
* [테스트](#테스트)


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

그다음 아래의 예시처럼 `myPlugin()` 래퍼 함수를 각자의 플러그인이나 테마에서 구현합니다.
이렇게 Continy 객체를 생성하면, 처음 호출 때 필요한 과정이 실행됩니다.

```php
<?php
/**
 * Plugin Name: My plugin
 * Description: ...
 * ... 
 */

require_once __DIR__ . './vendor/autoload.php';

// 플러그인에서 도움이 되는 래퍼 함수 생성
if ( !function_exists( 'myPlugin' ) ) {
    /**
     * Wrapper function
     * 
     * @return \ShoplicKr\Continy\Continy
     * @throws \ShoplicKr\Continy\ContinyException
     */
    function myPlugin(): ShoplicKr\Continy\Continy {
        static $continy = null;
        
        if (is_null($continy)) {
            $continy = ShoplicKr\Continy\ContinyFactory::create(__DIR__ . '/conf/setup.php');
        }
        
        return $continy;
    }
}

// 플러그인 시동하기
myPlugin();
```

## 모듈 설정하기

워드프레스의 플러그인과 테마 개발의 핵심은 적절한 액션, 혹은 필터를 추가하는 것입니다.
액션과 필터를 추가할 때는 반드시 콜백 함수를 명시하게 되어 있는데, 이 콜백 함수에서 우리가 원하는 동작을 구현합니다.

워드프레스 코어에 우리가 원하는 기능을 구현하기 위해야, 그리고 보다 쉽게 해당 기능을 관리하기 위해 '모듈'이라는 콤포넌트를 사용합니다.
적절한 기능들을 의미적으로 묶어 하나의 독립적인 모듈로 표현하는 것입니다.

Continy는 처음 실행될 때 이렇게 작성된 모듈을 불러오도록 되어 있습니다.
각 모듈은 지정한 add_action() 의 콜백으로 사용되며, 설정에서는 각 모듈의 add_action()을 제어할 수 있도록 옵션을 제공합니다.

설정은 직접 배열로 입력하거나, 배열을 리턴하는 파일의 경로를 지정할 수도 있습니다.
아래는 그 예입니다.

```php
<?php
// create() 부분만 예시로 듭니다.
// 옵션 #1: 설정 파일이 있는 경로 지정하기
$continy = ShoplicKr\Continy\ContinyFactory::create(__DIR__ . '/conf/setup.php');

// 옵션 #2: 직접 설정을 배열로 넣기
$continy = ShoplicKr\Continy\ContinyFactory::create(
    [
        'main_file' => __FILE__,
        'version'   => '1.0.0',
        'modules'   => [
            // ...
        ],
    ],
);
```

두 옵션은 결과적으로 같은 동작을 합니다. 설정 배열을 create()에 제공하는 것입니다.
이 배열의 구조를 파일 경로로 지정하는지, 아니면 직접 지정하는지 선택하는 것입니다.

## 테스트

`composer test:setup -- <db_name>> <db_user> <db_pass>` 를 입력하세요.
`tsts-core`, `tests-lib` 디렉토리가 각각 생성됩니다. 혹시 중간에 에러가 난다면 이 두 디렉토리를 깔끔히 지운 후 다시 스크립트를 실행하세요.

`composer test`로 유닛 테스트를 진행합니다.
