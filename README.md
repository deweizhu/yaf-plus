拥有yaf的高效和kohana的易用，它就是yaf plus。

**架构罗列**
+   Yaf提供基础MVC功能
+   Kohana类库：DB、Cache、Auth、Image、Session、Log、Http、Arr、Cookie、Date、UTF8、Text...
+   Twig模板引擎
+   额外附加类库FastDFS(Oss)、 SPL Observer...
+   基于PHP 7.0+ 开发


**使用说明**
+   nginx 指定根目录 public/ 
+   更改配置文件 conf/application.ini 中如db、cache等信息
+   在 php.ini 中添加 yaf.environ = "develop" 可开启错误提示
+   参看Demo模型，编写自己的代码。可使用kohana数据库查询构建器、原生SQL(支持PDO bind)、或继承Model类，更多使用方式请阅读Model等类。


**参考手册**
+   Yaf: http://yaf.laruence.com/manual/
+   Kohana: http://www.kohanaframework.org/3.3/guide/
+   twig: http://twig.sensiolabs.org/


`闲坐小窗读周易，不觉春去已多时；`
