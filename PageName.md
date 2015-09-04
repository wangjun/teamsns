TeamSNS
TeamSNS不是项目管理工具.我们已经有着足够多和足够难用的项目管理工具了,不需要再制造一个.

TeamSNS是一个基于web+轻客户端的团队交流工具.然而它又不是IM.也许这么说让人很晕,我们还是从头说起吧.

很久以前,有一群人提出了一个宣言,那个宣言改变了这个软件开发的世界.

个体与交互 重于 过程和工具可用的软件 重于 完备的文档客户协作   重于 合同谈判响应变化   重于 遵循计划对宣言的理解各有不同,简单的说,交流和务实是其重点.

经过数年的实践,重构宣言开始进入日程,其中,我个人很喜欢Jason Yip的版本.

•让我们互相交谈
•让我们构建软件给你看
•让我们彼此信任
•让我们对正在发生的一切和我们所学到的东西做出响应
TeamSNS就是在这样一种思想的指导下,针对十人以内的团队开发的交流工具.

这些人里边有开发者,有设计师,有项目经理,也有我们的目标客户.

他们可能并不能那么幸运的在一个房间里边工作.然而他们需要”互相交谈”,他们需要知道”正在发生的一切和学到的新东西”.

所以TeamSNS出现了.

以TODO为核心,以文档,webIM为辅助的沟通工具.

每个人可以看见其他人现在在做什么,接下来要做什么.每一件事情花了多久时间,遇到了什么问题,学到了什么东西.

而无需打扰正在专心用功的他/她.

客户可以清楚的知道项目的进度,了解项目的细节,不是总给你发邮件抱怨:

为什么一个页面能花掉10个小时?
而是在Team广播里说

遇到的困难比想象的多,但是进度却没有落后,大家都辛苦了~
TeamSNS试图将沟通变得简单,将团队内发生的事情可视化.敏捷团队的交流工具,这就是我们的定位.

图文教学

第一部分 近况

登录后,系统自动进入近况页面.我们先来看看页面布局.

<img src='http://teamsns.cn/wp-content/uploads/teamsns.cn/2009/08/guide1.jpg'>


1 设置区<br>
<br>
设置区是包含如下功能,小组的名字和简介设置(出现在左上角);个人页面和个人资料设置,其中包含了头像设置和密码修改.同事列表,可以查看团队里边全部的同事.<br>
<br>
2 功能分类<br>
<br>
目前TeamSNS包含四大块功能,文档,TODO,项目和报告.近况页面主要用于浏览团队最近发生的事情.<br>
<br>
3 同事列表<br>
<br>
所有的同事都被列到右边,点头像会弹出聊天窗口,点名字会进入其个人页面.当有新的私信时,发送人头像的背景会变为浅黄色.<br>
<br>
4 广播<br>
<br>
广播是可以同时发送给全部同事的消息,广播只有在近况页面出现,不会像同事列表一样一直出现在右侧.<br>
<br>
5 近况内容<br>
<br>
所有的同事的活动都将进入近况列表.你可以点击右侧下方的分类浏览某个人的近况,也可以点击”设置”,指定近况的类型<br>
<br>
第二部分 文档<br>
<br>
<img src='http://teamsns.cn/wp-content/uploads/teamsns.cn/2009/08/guide2.jpg'>


点击上图 2 处,即可进入可视化编辑状态,创建新文档的操作也在这里进行.<br>
<br>
<img src='http://teamsns.cn/wp-content/uploads/teamsns.cn/2009/08/guide3.jpg'>


按上图示意进行操作,保存文档后,会出现一个文档链接,点击进入即可对新的文档进行浏览和编辑.<br>
<br>
第三部分 TODO<br>
<br>
<img src='http://teamsns.cn/wp-content/uploads/teamsns.cn/2009/08/guide4.jpg'>


第四部分 报告<br>
<br>
<img src='http://teamsns.cn/wp-content/uploads/teamsns.cn/2009/08/guide5.jpg'>


客户端截图<br>
<br>
<img src='http://teamsns.cn/wp-content/uploads/teamsns.cn/2009/08/mm1.png'>
<img src='http://teamsns.cn/wp-content/uploads/teamsns.cn/2009/08/mm2.png'>




下载地址和安装说明<br>
<br>
安装说明<br>
<br>
1.svn checkout<br>
2.将得到的文件上传的支持php5的空间(php vserion >= 5.20)<br>
3.修改以下目录及目录下所有文件的属性为777<br>
session<br>
db<br>
code/config<br>
static/data<br>
3. 修改index.php，define( 'IN_SQLITE3', true);启用PDO_Sqlite3，为flase使用sqlite2<br>
4.通过浏览器访问即可<br>
5.默认管理员的账号为 email=admin@admin.com 密码=admin<br>
6.登陆后,右上方连接中有客户端在线安装页面.需要flash支持