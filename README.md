# PHP框架

## 思路
> 可以快速开发部署的PHP业务框架，与整体研发架构相配合

web框架（如yaf）解决了请求流转和分发的问题，将请求生命周期进行抽象和约束，形成标准流程，使得处理过程更加顺滑并且合乎规范；  
与此同时，我们也期待将处理web流程的模式套用过来，按照类似的方式处理cli命令执行以及长进程，使得这一切尽量按照统一的标准运作。

> [整体思路](think.md)

## 环境依赖

1. PHP7.1+

1. yaf/yac/redis/phpunit 稳定版

1. 编译安装 

   ```shell
   ./configure --prefix=/usr/local/php --with-mysqli=mysqlnd --with-pdo-mysql=mysqlnd --with-gd --with-jpeg-dir=/usr/lib64/ --with-iconv --with-openssl --with-curl --enable-pcntl --with-zlib --enable-bcmath --enable-json --enable-fpm --enable-mbstring --enable-soap --enable-opcache
   ```

## 使用

> 使用镜像 https://hub.docker.com/r/ifintech/php7/

#### 构建新项目
> 新构建一个名为demo，域名为demo.com的项目

1. 下载phplib，下载phplib-template（构建工具）

> ${dev-path} 为本地开发目录
```bash
cd ${dev-path}
git clone https://github.com/ifintech/phplib.git
git clone https://github.com/ifintech/phplib-template.git //构建工具
```
2. 构建

```shell
docker run -itd --name demo -p 80:80 -v /${dev-path}/phplib-template:/data1/htdocs/phplib-template -v /${dev-path}/phplib:/data1/htdocs/phplib -v /${dev-path}/demo:/data1/htdocs/demo ifintech/php7
docker exec -it demo /usr/local/php/bin/php /data1/htdocs/phplib-template/build/cg.php demo demo.com
```

3. 测试运行

```shell
curl -v http://127.0.0.1/ -H 'Host:demo.com'
```

#### 构建已存在项目
> 当别人有个demo的项目，我需要介入开发，项目为demo，域名为demo.com

1. 下载phplib和项目demo到本地开发目录${dev-path}

```bash
cd ${dev-path}
git clone https://github.com/ifintech/phplib.git
git clone https://github.com/ifintech/demo.git
```

2. 构建项目，初始化数据库等操作

```shell
docker run -itd --name mydemo -p 80:80 -v /${dev-path}/phplib:/data1/htdocs/phplib -v /${dev-path}/demo:/data1/htdocs/demo ifintech/phplib
docker exec -it demo /data1/htdocs/demo/build/build.sh
```

3. 测试运行

```shell
curl -v http://127.0.0.1/ -H 'Host:demo.com'
```

4. 本地开发,在/etc/hosts中添加如下行:
```shell
   127.0.0.1	demo.com
```

5. 通过demo.com:port进行访问（需要注意，如果映射端口不是80，需要修改nginx中的listen配置）

## 模式

* web请求
  phplib在yaf基础之上进行了扩充和改造，例如异常和错误处理、日志记录等，使得匹配不同项目的需求
* cli命令
  通过模拟web处理流程的方式处理cli命令，将每个任务的入口视作一个控制器，以标准的方式走完整个执行周期（应用初始化->启动项->插件->路由分发->任务执行）
* 长进程
  长进程指master-worker模式的守护进程，包括一个用以监控和管理子进程的主进程，和一个或多个处理实际任务的子进程。通常用于生产者－消费者模型，如消息队列、消息发布订阅等场景。 
 
  正常情况下，一个独立的长进程从队列中获取任务或者监听订阅的消息源。一旦获取到消息，工作进程会模拟一个fastcgi请求，将任务打包发给php-fpm，由fpm派发给其子进程执行任务，通过yaf分发给具体的控制器，按照web流程走完全部流程。  

  这样做的好处是：
  * fpm稳定性良好，几乎不存在内存泄露等情况
  * fpm自带进程管理功能，可以根据配置动态调整子进程数量
  * 任务获取和fastcig请求的逻辑可以使用类似go语言协程的模式完成，与任务的处理逻辑解耦合，使得整个流程的处理模式与web模式进行统一，更加规范


## 模块
> 模式是具有完整独立逻辑的子系统，有自己的controller，model，view

已有公共模块  

1. 管理系统模块
  - 用户及角色管理
  - 权限管理及登录
  - 菜单管理
  - 系统功能 （行为记录 安全控制）
  - 任务（任务池、领取任务、开始任务、完成任务、任务统计分析）
  - 消息通知（对接钉钉）
  - 审批流（发起审批，在钉钉完成审批流程，获取回调）
  
2. 微信模块  
  - oauth
  - 消息发送和消息接受
  - 客服
  
  
#### 模块启用

通过phplib-template初始化  

/usr/local/php/bin/php /data1/htdocs/phplib-template/cg.php demo demo.com Admin,Wechat

```bash
*******************************************************************************************
Code Generator Version 3.0.0
*******************************************************************************************

使用说明:

1. 执行当前脚本需要root权限

2. 执行命令示例
   /usr/bin/php /data1/htdocs/phplib-template/cg.php 项目名称 域名      模块列表(多个模块用英文逗号分割)
   /usr/bin/php /data1/htdocs/phplib-template/cg.php demo    demo.com admin,wechat
   
3. 生成的项目代码目录位置 - /data1/htdocs/{项目名称}

*******************************************************************************************
```
