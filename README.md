# shakeFlat
- A simple and practical PHP framework
- shakeFlat은 PHP framework입니다.

#### shakeFlat의 설계 의도
- 현업에서 쉽게 사용하기 위한 실용주의를 지향합니다.
- 웹프로그래밍의 기본적인 구조(Request-Response)가 프레임웍 설계의 기초가 되었습니다.
- PHP언어의 특성을 살린 직관적 구성을 가집니다.
- 누구나 어렵지 않게, 쉽게 접근 가능한 코드 구조를 지향합니다.

#### 설치
1. 코드를 다운로드 받으면 htdocs, shakeFlat 2개의 폴더가 나옵니다.
2. htdocs 폴더를 document_root 로 설정합니다.
3. (선택) 포함되어 있는 템플릿 예제를 사용하려면 shakeFlat/asset 폴더를 htdocs 아래에 심볼릭 링크를 만듭니다.
```
cd htdocs
ln -s ../shakeFlat/assets/ .
```

#### 사용법
1. 웹서버에서 모든 request 를 /index.php 가 받을 수 있도록 rewrite 설정을 합니다.
2. shakeFlat/config/config.ini 파일을 프로젝트에 맞게 수정합니다.
3. htdocs/index.php 예제 코드는 다음과 같습니다.
```php
<?php
require_once __DIR__ . "/../shakeFlat/core/autoloader.inc";

$app = new shakeFlat\App();
$app->setTransaction()->setTemplate("default")->setMode(shakeFlat\Template::MODE_WEB);
$app->execModule()->publish();
```
3. shakeFlat/modules/ 아래에 개별 웹페이지의 PHP코드 부분을 작성합니다. (welcome/main.php 참조)
4. shakeFlat/templates/[템플릿이름]/ 아래에 개별 웹페이지의 HTML템플릿 부분을 작성합니다. (default/welcome/main.html 참조)
5. 이렇게 작성된 웹페이지는 https://yourdomain/[module_name]/[fnc_name] URL로 접근 가능합니다.
       예를 들어 dashboard/table.php 를 작성하였다면 https://yourdomain/dashboard/table 으로 접근 할 수 있습니다.
       참고로 웹사이트의 홈(첫 페이지)은 welcome/main.php 입니다.

#### 템플릿 구조
1. shakeFlat/templates/ 아래에 여러개의 템플릿을 만들 수 있습니다.
2. 각 템플릿은 폴더 이름으로 규정 됩니다.
3. 하나의 템플릿은 2개의 기본파일이 존재하여야 합니다.
        예를 들어 shakeFlat/templates/[템플릿이름]/ 아래에는 다음과 같이 2개의 기본파일이 존재합니다.
```
layout.html   : 웹페이지의 전체적인 구조를 가진다.
error.html    : 에러가 발생한 경우 이 템플릿이 사용된다.
```
4. 패키지에 포함된 shakeFlat/templates/default/ 와 shakeFlat/templates/mobile 을 참조하십시오.

#### 계획
1. 에러 처리 핸들러
2. 다중 언어 웹사이트 개발 지원 기능
