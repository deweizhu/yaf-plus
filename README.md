﻿**楔子**

`追求卓越性能，PHP框架只选Yaf`  
 

**总纲**
+   Yaf提供基础MVC功能
+   Twig模板引擎
+   额外附加类库 SPL Observer、Model...
+   基于PHP 7.0+ 开发
+   支持SQL Server/MySQL 
+   支持composer 
+   支持swoole (未在生产环境下使用，仅测试)


**用法**
+   nginx 指定根目录 public/ 
+   更改配置文件 conf/application.ini 中如db、cache等信息
+   启用命名空间，修改 php.ini 添加 yaf.use_namespace = 1
+   「可选」在 php.ini 中添加 yaf.environ = "develop" 可开启错误提示
+   参看Demo模型，编写自己的代码。可使用kohana数据库查询构建器、原生SQL(支持PDO bind)、或继承Model类，更多使用方式请阅读Model等类。


**手册**
+   Yaf: <https://yaf.laruence.com/manual/>
+   Kohana: <https://github.com/kohana/kohana/>
+   模板twig: <https://twig.symfony.com/>


