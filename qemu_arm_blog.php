<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="generator" content="AsciiDoc 8.6.8">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>利用qemu模拟嵌入式系统制作全过程</title>
<link href="http://172.16.11.220:8080/css/bootstrap.min.css" rel="stylesheet" media="screen">
<link href="http://172.16.11.220:8080/css/main.css" rel="stylesheet">


<link rel="stylesheet" href="http://172.16.11.220:8080/css/toc2.css" type="text/css">

<script type="text/javascript" src="http://172.16.11.220:8080/js/asciidoc.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
asciidoc.install(2);
/*]]>*/
</script>



</head>
<body class="article">
		<?php
			require 'core/nav.inc';
		?>

<div class="container">
<div class="row-fluid">
<div class="span3">
<div id="article_nav" class="row-fluid"> Article Nav </div>
<div id="article_commnet" class="row-fluid"> Comment </div>
</div>
<div class="span9" id="content">
<div id="header">
<h1>利用qemu模拟嵌入式系统制作全过程</h1>
<span id="author">Pingbo Wen</span><br>
<span id="email" class="monospaced">&lt;<a href="mailto:wengpingbo@gmail.com">wengpingbo@gmail.com</a>&gt;</span><br>
<span id="revdate">2013/08/11</span>
</div>
<div id="preamble">
<div class="sectionbody">
<div class="paragraph"><p>这篇文章，将介绍如何用qemu来搭建一个基于ARM的嵌入式linux系统。通过该文章，你可以学习到如何配置kernel，如何交叉编译kernel，如何配置busybox并编译，如何制作initramfs，如何制作根文件系统，如何定制自己的uboot，如何通过uboot向kernel传递参数等。开始干活！</p></div>
</div>
</div>
<div class="sect1">
<h2 id="_环境搭建">环境搭建</h2>
<div class="sectionbody">
<div class="paragraph"><p>在实现我们的目标之前，我们需要搭建自己的工作环境。在这里，假设你的主机上已经有gcc本地编译环境，并运行Ubuntu 12.10。但是这并不影响在其他的linux平台上进行，只要修改一下对应的命令就可以了。</p></div>
<div class="paragraph"><p>首先，我们需要下载一个ARM交叉工具链。你可以在网上下载源码自己编译，也可以下载已经编译好的工具链。在工具链中有基本的ARM编译工具，比如：gcc, gdb, addr2line, nm, objcopy, objdump等。可能你会问，这些工具本机不是已经有了么？如果不出意外，我想你的主机应该是x86架构的。不同的架构，有不同的指令集，你不能拿一个x86的执行文件放到一个ARM机器上执行。所以我们需要一个能够在x86架构上生成ARM可执行程序的GCC编译器。有很多预先编译好的ARM工具链，这里使用的是CodeSourcery <span class="footnote span9 pull-right"><br>[Download CodeSourcery: <a href="https://sourcery.mentor.com/GNUToolchain/release2449">https://sourcery.mentor.com/GNUToolchain/release2449</a>]<br></span> 。更多关于toolchain的信息可以在elinux.org找到 <span class="footnote span9 pull-right"><br>["toolchain: <a href="http://elinux.org/Toolchains">http://elinux.org/Toolchains</a>"]<br></span> 。下载下来后，直接解压，放到某个目录，然后配置一下PATH环境变量，这里是这样配置的：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt><span style="font-weight: bold"><span style="color: #0000FF">export</span></span> <span style="color: #009900">PATH</span><span style="color: #990000">=~</span>/arm-<span style="color: #993399">2013.05</span>/bin<span style="color: #990000">:</span><span style="color: #009900">$PATH</span></tt></pre></div></div>
<div class="paragraph"><p>配置完ARM交叉工具链后，我们需要下载一些源码，并安装一些软件。
命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt><span style="font-style: italic"><span style="color: #9A1900"># install qemu</span></span>
sudo apt-get install qemu qemu-kvm qemu-kvm-extras qemu-user qemu-system
<span style="font-style: italic"><span style="color: #9A1900"># install mkimage tool</span></span>
sudo apt-get install uboot-mkimage
<span style="font-style: italic"><span style="color: #9A1900"># install git</span></span>
sudo apt-get install git
<span style="font-style: italic"><span style="color: #9A1900"># prepare related directory</span></span>
mkdir -pv <span style="color: #990000">~</span>/armsource<span style="color: #990000">/</span>{kernel<span style="color: #990000">,</span>uboot<span style="color: #990000">,</span>ramfs<span style="color: #990000">,</span>busybox}
<span style="font-style: italic"><span style="color: #9A1900"># download latest kernel stable code to kernel dir</span></span>
git clone http<span style="color: #990000">:</span>//git<span style="color: #990000">.</span>kernel<span style="color: #990000">.</span>org/pub/scm/linux/kernel/git/stable/linux-stable<span style="color: #990000">.</span>git <span style="color: #990000">~</span>/armsource/kernel
<span style="font-style: italic"><span style="color: #9A1900"># download latest u-boot code to uboot dir</span></span>
git clone git<span style="color: #990000">:</span>//git<span style="color: #990000">.</span>denx<span style="color: #990000">.</span>de/u-boot<span style="color: #990000">.</span>git <span style="color: #990000">~</span>/armsource/uboot
<span style="font-style: italic"><span style="color: #9A1900"># download latest busybox code to busybox dir</span></span>
git clone git<span style="color: #990000">:</span>//busybox<span style="color: #990000">.</span>net/busybox<span style="color: #990000">.</span>git <span style="color: #990000">~</span>/armsource/busybox</tt></pre></div></div>
</div>
</div>
<div class="sect1">
<h2 id="_配置kernel">配置kernel</h2>
<div class="sectionbody">
<div class="paragraph"><p>环境搭建完后，我们就正式进入主题了。现在我们需要配置kernel源码，编译，并用qemu运行我们自己编译的kernel。这样我们就能够对我们的kernel进行测试，并做出对应的修改。</p></div>
<div class="paragraph"><p>进入kernel源码目录，我们需要找最新的kernel稳定版本。在写这篇文章的时候，最新的稳定版本是3.10.10。我们可以通过git切换到3.10.10。由于我们编译的内核需要运行在ARM上，所以我们应该到arch/arm/configs下找到对应我们设备的kernel配置文件。但是我们没有实际意义上的设备，而是用qemu模拟的设备，所以我们应该选择qemu能够模拟的设备的配置文件。这里我们选择常用的versatile_defconfig。
对应的命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>cd <span style="color: #990000">~</span>/armsource/kernel
<span style="font-style: italic"><span style="color: #9A1900"># checkout a tag and create a branch</span></span>
git checkout v3<span style="color: #990000">.</span><span style="color: #993399">10.10</span> -b linux-<span style="color: #993399">3.10</span><span style="color: #990000">.</span><span style="color: #993399">10</span>
<span style="font-style: italic"><span style="color: #9A1900"># create .config file</span></span>
make versatile_defconfig <span style="color: #009900">ARCH</span><span style="color: #990000">=</span>arm</tt></pre></div></div>
<div class="paragraph"><p>配置完了，我们就可以编译了。编译的时候，我们可以用多个线程来加速编译，具体用多少个就要看你主机的配置了。这里我们用12个线程编译，命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>make -j<span style="color: #993399">12</span> <span style="color: #009900">ARCH</span><span style="color: #990000">=</span>arm <span style="color: #009900">CROSS_COMPILE</span><span style="color: #990000">=</span>arm-none-linux-gnueabi-</tt></pre></div></div>
<div class="paragraph"><p>注意，如果交叉编译环境没有配置好，这个地方会提示找不到对应的gcc编译器。这里-j12是指定编译线程为12个，ARCH是指定目标架构为arm，所用的交叉编译器arm-none-linux-gnueabi-。</p></div>
<div class="paragraph"><p>OK，kernel已经编译好了，那么我们需要用qemu把它跑起来。关于qemu的具体使用，请看qemu的官方文档 <span class="footnote span9 pull-right"><br>[Qemu User Document: <a href="http://qemu.weilnetz.de/qemu-doc.html">http://qemu.weilnetz.de/qemu-doc.html</a>]<br></span> ，这里直接给出命令：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>qemu-system-arm -M versatilepb -kernel arch/arm/boot/zImage -nographic</tt></pre></div></div>
<div class="paragraph"><p>这里-M是指定模拟的具体设备型号，versatile系列的pb版本，-kernel指定的是对应的内核，-nographic是把qemu输出直接导向到当前终端。</p></div>
<div class="paragraph"><p>好，命令成功执行了。但是，好像没有任何有效输出。我们通过C-a x来退出qemu。编译的kernel好像不怎么好使，配置文件肯定有问题。打开.config配置文件，发现传递给kernel的参数没有指定console，难怪没有输出。我们定位到CMDLINE，并加入console参数：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt><span style="color: #009900">CONFIG_CMDLINE</span><span style="color: #990000">=</span><span style="color: #FF0000">"console=ttyAMA0 root=/dev/ram0"</span></tt></pre></div></div>
<div class="paragraph"><p>保存.config，重新编译kernel，并用qemu加载。现在终于有输出了。如果不出意外，kernel应该会停在找不到根文件系统，并跳出一个panic。为什么会找不到根文件系统？因为我们压根就没有给它传递过，当然找不到。</p></div>
<div class="paragraph"><p>那现在是不是应该制作我们自己的根文件系统了。先别急，为了让后面的路好走一点，我们这里还需对内核进行一些配置。首先，我们需要用ARM EABI去编译kernel，这样我们才能让kernel运行我们交叉编译的用户态程序，因为我们所有的程序都是用gnueabi的编译器编译的。具体可以看wikipedia相关页面 <span class="footnote span9 pull-right"><br>[EABI: <a href="http://en.wikipedia.org/wiki/Application_binary_interface">http://en.wikipedia.org/wiki/Application_binary_interface</a>]<br></span> ，你也可以简单的理解为嵌入式的ABI。其次，我们需要把对kernel module的支持去掉，这样可以把相关的驱动都编译到一个文件里，方便我们之后的加载。</p></div>
<div class="paragraph"><p>当然，你可以使能kernel的debug选项，这样就可以调试内核了，并打印很多调试信息。这里就不再说了，如果感兴趣，可以看我之前写的关于kernel调试的文章 <span class="footnote span9 pull-right"><br>[kernel debug]<br></span> 。</p></div>
<div class="paragraph"><p>总结起来，这一次我们对.config做了如下修改：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt><span style="font-style: italic"><span style="color: #9A1900"># CONFIG_MODULES is not set</span></span>
<span style="color: #009900">CONFIG_AEABI</span><span style="color: #990000">=</span>y
<span style="color: #009900">CONFIG_OABI_COMPAT</span><span style="color: #990000">=</span>y
<span style="color: #009900">CONFIG_PRINTK_TIME</span><span style="color: #990000">=</span>y
<span style="color: #009900">CONFIG_EARLY_PRINTK</span><span style="color: #990000">=</span>y
<span style="color: #009900">CONFIG_CMDLINE</span><span style="color: #990000">=</span><span style="color: #FF0000">"earlyprintk console=ttyAMA0 root=/dev/ram0"</span></tt></pre></div></div>
</div>
</div>
<div class="sect1">
<h2 id="_通过busybox制作initramfs镜像">通过busybox制作initramfs镜像</h2>
<div class="sectionbody">
<div class="paragraph"><p>如果你注意到了之前传递给kernel的参数，你会发现有一个root=/dev/ram0的参数。没错，这就是给kernel指定的根文件系统，kernel检查到这个参数的时候，会到指定的地方加载根文件系统，并执行其中的init程序。这样就不会出现刚才那种情况，找不到根文件系统了。</p></div>
<div class="paragraph"><p>我们的目标就是让kernel挂载我们的ramfs根文件系统，并且在执行init程序的时候，调用busybox中的一个shell，这样我们就有一个可用的shell来和系统进行交互了。</p></div>
<div class="paragraph"><p>整个ramfs中的核心就是一个busybox可执行文件。busybox就像是一把瑞士军刀，可以把很多linux下的命令(比如：cp, rm, whoami等)全部集成到一个可执行文件 <span class="footnote span9 pull-right"><br>[Busybox: <a href="http://www.ibm.com/developerworks/library/l-busybox/">http://www.ibm.com/developerworks/library/l-busybox/</a>]<br></span> 。这为制作嵌入式根文件系统提供了很大的便利，开发者不用单独编译每一个要支持的命令，还不用考虑库的依赖关系。基本上每一个制作嵌入式系统的开发者的首选就是busybox。</p></div>
<div class="paragraph"><p>busybox也是采用Kconfig来管理配置选项，所以配置和编译busybox和kernel没有多大区别。busybox很灵活，你可以自由取舍你想要支持的命令，并且还可以添加你自己写的程序。在编译busybox的时候，为了简单省事，我们这里采用静态编译，这样就不用为busybox准备其他libc，ld等依赖库了。</p></div>
<div class="paragraph"><p>具体命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>cd <span style="color: #990000">~</span>/armsource/busybox
<span style="font-style: italic"><span style="color: #9A1900"># using stable version 1.21</span></span>
git checkout origin<span style="color: #990000">/</span>1_21_stable -b busybox-<span style="color: #993399">1.21</span>
<span style="font-style: italic"><span style="color: #9A1900"># using default configure</span></span>
make defconfig <span style="color: #009900">ARCH</span><span style="color: #990000">=</span>arm
<span style="font-style: italic"><span style="color: #9A1900"># compile busybox in static</span></span>
make menuconfig
make -j<span style="color: #993399">12</span> <span style="color: #009900">ARCH</span><span style="color: #990000">=</span>arm <span style="color: #009900">CROSS_COMPILE</span><span style="color: #990000">=</span>arm-none-linux-gnueabi-</tt></pre></div></div>
<div class="paragraph"><p>编译完后，我们就得到一个busybox静态链接的文件。</p></div>
<div class="paragraph"><p>接下来，我们需要一个init程序。这个程序将是kernel执行的第一个用户态的程序，我们需要它来产生一个可交互的shell。在桌面级别的linux发行版本，使用的init程序一般是System V init(传统的init)，upstart(ubuntu)，systemd(fedora)等。busybox也带有一个init程序，但是我们想自己写一个。既然自己写，那有两种实现方式，用C和libc实现，或者写一个shell脚本。</p></div>
<div class="paragraph"><p>为了简单，这里选择后者，具体脚本如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt><span style="font-style: italic"><span style="color: #9A1900">#!/bin/sh</span></span>
echo
echo <span style="color: #FF0000">"###########################################################"</span>
echo <span style="color: #FF0000">"## THis is a init script for initrd/initramfs            ##"</span>
echo <span style="color: #FF0000">"## Author: WEN Pingbo &lt;wengpingbo@gmail.com&gt;             ##"</span>
echo <span style="color: #FF0000">"## Date: 2013/08/17 16:27:34 CST                         ##"</span>
echo <span style="color: #FF0000">"###########################################################"</span>
echo

<span style="color: #009900">PATH</span><span style="color: #990000">=</span><span style="color: #FF0000">"/bin:/sbin:/usr/bin:/usr/sbin"</span>

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -f <span style="color: #FF0000">"/bin/busybox"</span> <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"cat not find busybox in /bin dir, exit"</span>
  <span style="font-weight: bold"><span style="color: #0000FF">exit</span></span> <span style="color: #993399">1</span>
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>

<span style="color: #009900">BUSYBOX</span><span style="color: #990000">=</span><span style="color: #FF0000">"/bin/busybox"</span>

echo <span style="color: #FF0000">"build root filesystem..."</span>
<span style="color: #009900">$BUSYBOX</span> --install -s

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -d /proc <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"/proc dir not exist, create it..."</span>
  <span style="color: #009900">$BUSYBOX</span> mkdir /proc
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>
echo <span style="color: #FF0000">"mount proc fs..."</span>
<span style="color: #009900">$BUSYBOX</span> mount -t proc proc /proc

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -d /dev <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"/dev dir not exist, create it..."</span>
  <span style="color: #009900">$BUSYBOX</span> mkdir /dev
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>
<span style="font-style: italic"><span style="color: #9A1900"># echo "mount tmpfs in /dev..."</span></span>
<span style="font-style: italic"><span style="color: #9A1900"># $BUSYBOX mount -t tmpfs dev /dev</span></span>

<span style="color: #009900">$BUSYBOX</span> mkdir -p /dev/pts
echo <span style="color: #FF0000">"mount devpts..."</span>
<span style="color: #009900">$BUSYBOX</span> mount -t devpts devpts /dev/pts

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -d /sys <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"/sys dir not exist, create it..."</span>
  <span style="color: #009900">$BUSYBOX</span> mkdir /sys
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>
echo <span style="color: #FF0000">"mount sys fs..."</span>
<span style="color: #009900">$BUSYBOX</span> mount -t sysfs sys /sys

echo <span style="color: #FF0000">"/sbin/mdev"</span> <span style="color: #990000">&gt;</span> /proc/sys/kernel/hotplug
echo <span style="color: #FF0000">"populate the dev dir..."</span>
<span style="color: #009900">$BUSYBOX</span> mdev -s

echo <span style="color: #FF0000">"drop to shell..."</span>
<span style="color: #009900">$BUSYBOX</span> sh

<span style="font-weight: bold"><span style="color: #0000FF">exit</span></span> <span style="color: #993399">0</span></tt></pre></div></div>
<div class="paragraph"><p>我们把这个脚本保存在~/armsource目录下。在这个脚本中，我们通过busybox --install -s来构建基本文件系统，挂载相应的虚拟文件系统，然后就调用busybox自带的shell。</p></div>
<div class="paragraph"><p>现在我们已经编译好了busybox，并准备好了相应的init脚本。我们需要考虑根文件系统的目录结构了。kenel支持很多种文件系统，比如：ext4, ext3, ext2, cramfs, nfs, jffs2, reiserfs等，还包括一些伪文件系统: sysfs, proc, ramfs等。而在kernel初始化完成后，会尝试挂载一个它所支持的根文件系统。根文件系统的目录结构标准是FHS，由一些kernel开发者制定，感兴趣的可以看wikipedia相关页面 <span class="footnote span9 pull-right"><br>[FHS: <a href="http://en.wikipedia.org/wiki/Filesystem_Hierarchy_Standard">http://en.wikipedia.org/wiki/Filesystem_Hierarchy_Standard</a>]<br></span> 。</p></div>
<div class="paragraph"><p>由于我们要制作一个很简单的ramfs，其中只有一个busybox可执行文件，所以我们没必要过多的考虑什么标准。只需一些必须的目录结构就OK。这里，我们使用的目录结构如下：
.Directory Tree</p></div>
<div class="listingblock">
<div class="content monospaced">
<pre>├── bin
│   ├── busybox
│   └── sh -&gt; busybox
├── dev
│   └── console
├── etc
│   └── init.d
│       └── rcS
├── init
├── sbin
└── usr
    ├── bin
    └── sbin</pre>
</div></div>
<div class="paragraph"><p>你可以通过如下命令来创建这个文件系统：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>cd <span style="color: #990000">~</span>/armsource/ramfs
mkdir -pv bin dev etc/init<span style="color: #990000">.</span>d sbin user<span style="color: #990000">/</span>{bin<span style="color: #990000">,</span>sbin}
cp <span style="color: #990000">~</span>/armsource/busybox/busybox bin<span style="color: #990000">/</span>
ln -s busybox bin/sh
mknod -m <span style="color: #993399">644</span> dev/console c <span style="color: #993399">5</span> <span style="color: #993399">1</span>
cp <span style="color: #990000">~</span>/armsource/init <span style="color: #990000">.</span>
touch etc/init<span style="color: #990000">.</span>d/rcS
chmod <span style="color: #990000">+</span>x bin/busybox etc/init<span style="color: #990000">.</span>d/rcS init</tt></pre></div></div>
<div class="paragraph"><p>现在我们有了基本的initramfs，万事具备了，就差点东风了。这个东风就是怎样制作intramfs镜像，并让kernel加载它。</p></div>
<div class="paragraph"><p>在kernel文档中，对initramfs和initrd有详细的说明 <span class="footnote span9 pull-right"><br>[initrd: <a href="http://www.ibm.com/developerworks/library/l-initrd/index.html">http://www.ibm.com/developerworks/library/l-initrd/index.html</a>]<br></span> <span class="footnote span9 pull-right"><br>[Initrd/Initramfs: <a href="http://wiki.sourcemage.org/HowTo(2f)Initramfs.html">http://wiki.sourcemage.org/HowTo(2f)Initramfs.html</a>]<br></span> 。initramfs其实就是一个用gzip压缩的cpio文件。我们可以把initramfs直接集成到kernel里，也可以单独加载initramfs。在kernel源码的scripts目录下，有一个gen_initramfs_list.sh脚本，专门是用来生成initramfs镜像和initramfs list文件。你可以通过如下方式自动生成initramfs镜像：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>sh scripts/gen_initramfs_list<span style="color: #990000">.</span>sh -o ramfs<span style="color: #990000">.</span>gz <span style="color: #990000">~</span>/armsource/ramfs</tt></pre></div></div>
<div class="paragraph"><p>然后修改kernel的.config配置文件来包含这个文件：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt><span style="color: #009900">CONFIG_INITRAMFS_SOURCE</span><span style="color: #990000">=</span><span style="color: #FF0000">"ramfs.gz"</span></tt></pre></div></div>
<div class="paragraph"><p>重新编译后，kernel就自动集成了你制作的ramfs.gz，并会在初始化完成后，加载这个根文件系统，并产生一个shell。</p></div>
<div class="paragraph"><p>你也可以用gen_initramfs_list.sh脚本生成一个列表文件，然后CONFIG_INITRAMFS_SOURCE中指定这个列表文件。也可以把你做的根文件系统自动集成到kernel里面。命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>sh scripts/gen_initramfs_list<span style="color: #990000">.</span>sh <span style="color: #990000">~</span>/armsource/ramfs <span style="color: #990000">&gt;</span> initramfs_list</tt></pre></div></div>
<div class="paragraph"><p>对应的内核配置：CONFIG_INITRAMFS_SOURCE="initramfs_list"</p></div>
<div class="paragraph"><p>但是这里并不打算这么做，我们自己手动制作initramfs镜像，然后外部加载。命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>cd <span style="color: #990000">~</span>/armsource/ramfs
find <span style="color: #990000">.</span> <span style="color: #990000">|</span> cpio -o -H newc <span style="color: #990000">|</span> gzip -<span style="color: #993399">9</span> <span style="color: #990000">&gt;</span> ramfs<span style="color: #990000">.</span>gz</tt></pre></div></div>
<div class="quoteblock">
<div class="content">
<div class="paragraph"><p>选项-H是用来指定生成的格式。</p></div>
</div>
<div class="attribution">
&#8212; cite source
</div></div>
<div class="paragraph"><p>手动生成ramfs.gz后，我们就可以通过qemu来加载了，命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>qemu-system-arm -M versatilepb -kernel arch/arm/boot/zImage -nographic -initrd ramfs<span style="color: #990000">.</span>gz</tt></pre></div></div>
<div class="paragraph"><p>现在我们的系统起来了，并且正确执行了我们自己写的脚本，进入了shell。我们可以在里面执行基本常用的命令。是不是有点小兴奋。</p></div>
</div>
</div>
<div class="sect1">
<h2 id="_配置物理文件系统_切换根文件系统">配置物理文件系统，切换根文件系统</h2>
<div class="sectionbody">
<div class="paragraph"><p>不是已经配置了根文件系统，并加载了，为什么还需要切换呢？可能你还沉浸在刚才的小兴奋里，但是，很不幸的告诉你。现在制作的小linux系统还不是一个完全的系统，因为没有完成基本的初始化，尽管看上去好像已经完成了。</p></div>
<div class="paragraph"><p>在linux中initramfs和initrd只是一个用于系统初始化的小型文件系统，通常用来加载一些第三方的驱动。为什么要通过这种方式来加载驱动呢？因为由于版权协议的关系，如果要把驱动放到kernenl里，意味着你必须要开放源代码。但是有些时候，一些商业公司不想开源自己的驱动，那它就可以把驱动放到initramfs或者initrd。这样既不违背kernel版权协议，又达到不开源的目的。也就是说在正常的linux发行版本中，kernel初始化完成后，会先挂载initramfs/initrd，来加载其他驱动，并做一些初始化设置。然后才会挂载真真的根文件系统，通过一个switch_root来切换根文件系统，执行第二个init程序，加载各种用户程序。在这中间，linux kernel跳了两下。</p></div>
<div class="paragraph"><p>既然他们跳了两下，那我们也跳两下。第一下已经跳了，现在的目标是制作物理文件系统，并修改initramfs中的init脚本，来挂载我们物理文件系统，并切换root文件系统，执行对应的init。</p></div>
<div class="paragraph"><p>为了省事，我们直接把原先的initramfs文件系统复制一份，当作物理根文件系统。由于是模拟，所以我们直接利用dd来生成一个磁盘镜像。具体命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>dd <span style="font-weight: bold"><span style="color: #0000FF">if</span></span><span style="color: #990000">=</span>/dev/zero <span style="color: #009900">of</span><span style="color: #990000">=~</span>/armsource/hda<span style="color: #990000">.</span>img <span style="color: #009900">bs</span><span style="color: #990000">=</span><span style="color: #993399">1</span> <span style="color: #009900">count</span><span style="color: #990000">=</span>10M
mkfs -t ext2 hda<span style="color: #990000">.</span>img
mount hda<span style="color: #990000">.</span>img /mnt
cp -r <span style="color: #990000">~</span>/armsource/ramfs<span style="color: #990000">/*</span> /mnt
umount /mnt</tt></pre></div></div>
<div class="paragraph"><p>这样hda.img就是我们制作的物理根文件系统，ext2格式。现在我们需要修改原先在initramfs中的init脚本，让其通过busybox的switch_root功能切换根文件系统。这里需要注意的是，在切换根文件系统时，不能直接调用busybox的switch_root，而是需要通过exec来调用。这样才能让最终的init进程pid为1。</p></div>
<div class="paragraph"><p>修改后的init脚本如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt><span style="font-style: italic"><span style="color: #9A1900">#!/bin/sh</span></span>
echo
echo <span style="color: #FF0000">"###########################################################"</span>
echo <span style="color: #FF0000">"## THis is a init script for sd ext2 filesystem          ##"</span>
echo <span style="color: #FF0000">"## Author: WEN Pingbo &lt;wengpingbo@gmail.com&gt;             ##"</span>
echo <span style="color: #FF0000">"## Date: 2013/08/17 16:27:34 CST                         ##"</span>
echo <span style="color: #FF0000">"###########################################################"</span>
echo

<span style="color: #009900">PATH</span><span style="color: #990000">=</span><span style="color: #FF0000">"/bin:/sbin:/usr/bin:/usr/sbin"</span>

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -f <span style="color: #FF0000">"/bin/busybox"</span> <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"cat not find busybox in /bin dir, exit"</span>
  <span style="font-weight: bold"><span style="color: #0000FF">exit</span></span> <span style="color: #993399">1</span>
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>

<span style="color: #009900">BUSYBOX</span><span style="color: #990000">=</span><span style="color: #FF0000">"/bin/busybox"</span>

echo <span style="color: #FF0000">"build root filesystem..."</span>
<span style="color: #009900">$BUSYBOX</span> --install -s

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -d /proc <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"/proc dir not exist, create it..."</span>
  <span style="color: #009900">$BUSYBOX</span> mkdir /proc
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>
echo <span style="color: #FF0000">"mount proc fs..."</span>
<span style="color: #009900">$BUSYBOX</span> mount -t proc proc /proc

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -d /dev <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"/dev dir not exist, create it..."</span>
  <span style="color: #009900">$BUSYBOX</span> mkdir /dev
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>
<span style="font-style: italic"><span style="color: #9A1900"># echo "mount tmpfs in /dev..."</span></span>
<span style="font-style: italic"><span style="color: #9A1900"># $BUSYBOX mount -t tmpfs dev /dev</span></span>

<span style="color: #009900">$BUSYBOX</span> mkdir -p /dev/pts
echo <span style="color: #FF0000">"mount devpts..."</span>
<span style="color: #009900">$BUSYBOX</span> mount -t devpts devpts /dev/pts

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -d /sys <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"/sys dir not exist, create it..."</span>
  <span style="color: #009900">$BUSYBOX</span> mkdir /sys
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>
echo <span style="color: #FF0000">"mount sys fs..."</span>
<span style="color: #009900">$BUSYBOX</span> mount -t sysfs sys /sys

echo <span style="color: #FF0000">"/sbin/mdev"</span> <span style="color: #990000">&gt;</span> /proc/sys/kernel/hotplug
echo <span style="color: #FF0000">"populate the dev dir..."</span>
<span style="color: #009900">$BUSYBOX</span> mdev -s

echo <span style="color: #FF0000">"dev filesystem is ok now, log all in kernel kmsg"</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg

echo <span style="color: #FF0000">"you can add some third part driver in this phase..."</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg
echo <span style="color: #FF0000">"begin switch root directory to sd card"</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg

<span style="color: #009900">$BUSYBOX</span> mkdir /newroot
<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #990000">!</span> -b <span style="color: #FF0000">"/dev/mmcblk0"</span> <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"can not find /dev/mmcblk0, please make sure the sd \</span>
<span style="color: #FF0000">        card is attached correctly!"</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg
  echo <span style="color: #FF0000">"drop to shell"</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg
  <span style="color: #009900">$BUSYBOX</span> sh
<span style="font-weight: bold"><span style="color: #0000FF">else</span></span>
  <span style="color: #009900">$BUSYBOX</span> mount /dev/mmcblk<span style="color: #993399">0</span> /newroot
  <span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #009900">$?</span> -eq <span style="color: #993399">0</span> <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
        echo <span style="color: #FF0000">"mount root file system successfully..."</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg
  <span style="font-weight: bold"><span style="color: #0000FF">else</span></span>
        echo <span style="color: #FF0000">"failed to mount root file system, drop to shell"</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg
        <span style="color: #009900">$BUSYBOX</span> sh
  <span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span>

<span style="font-style: italic"><span style="color: #9A1900"># the root file system is mounted, clean the world for new root file system</span></span>
echo <span style="color: #FF0000">""</span> <span style="color: #990000">&gt;</span> /proc/sys/kernel/hotplug
<span style="color: #009900">$BUSYBOX</span> umount -f /proc
<span style="color: #009900">$BUSYBOX</span> umount -f /sys
<span style="color: #009900">$BUSYBOX</span> umount -f /dev/pts
<span style="font-style: italic"><span style="color: #9A1900"># $BUSYBOX umount -f /dev</span></span>

echo <span style="color: #FF0000">"enter new root..."</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg
<span style="font-weight: bold"><span style="color: #0000FF">exec</span></span> <span style="color: #009900">$BUSYBOX</span> switch_root -c /dev/console /newroot /init

<span style="font-weight: bold"><span style="color: #0000FF">if</span></span> <span style="color: #990000">[</span> <span style="color: #009900">$?</span> -ne <span style="color: #993399">0</span> <span style="color: #990000">];</span><span style="font-weight: bold"><span style="color: #0000FF">then</span></span>
  echo <span style="color: #FF0000">"enter new root file system failed, drop to shell"</span> <span style="color: #990000">&gt;&gt;</span> /dev/kmsg
  <span style="color: #009900">$BUSYBOX</span> mount -t proc proc /proc
  <span style="color: #009900">$BUSYBOX</span> sh
<span style="font-weight: bold"><span style="color: #0000FF">fi</span></span></tt></pre></div></div>
<div class="paragraph"><p>现在我们可以通过qemu来挂载hda.img，为了简单，我们这里把这个设备虚拟为sd卡，这也是为什么上面的init脚本挂载物理根文件系统时，是找/dev/mmcblk0了。具体命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>qemu-system-arm -M versatilepb -kernel arch/arm/boot/zImage -nographic -initrd ramfs<span style="color: #990000">.</span>gz -sd hda<span style="color: #990000">.</span>img</tt></pre></div></div>
<div class="paragraph"><p>如果不出意外，你可以看到这个自己做的linux系统，通过调用两个init脚本，跳到最终的hda.img上的文件系统。</p></div>
</div>
</div>
<div class="sect1">
<h2 id="_配置uboot_加载kernel">配置Uboot，加载kernel</h2>
<div class="sectionbody">
<div class="paragraph"><p>可能到这里，你觉得，终于把整个流程走了一遍了。但是，还差一环。之前我们都是通过qemu来直接加载我们的kernel，initramfs和物理镜像，但是在真真的嵌入式设备，这些加载过程都需要你好好考虑。那么在这一节，我们借助uboot来模拟加载过程。</p></div>
<div class="paragraph"><p>我们的目标是让uboot来加载kernel，initramfs，并识别qemu虚拟的sd卡设备。这里我们通过tftp来向uboot传递kernel和initramfs镜像。既然要依靠uboot来加载系统镜像，那么需要按照uboot的镜像格式制作加载的镜像。而mkimage工具，就是干这活的。在制作uboot镜像时，我们需要指定镜像类型，加载地址，执行地址等，制作uboot版的initramfs命令如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>mkimage -A arm -O linux -T ramdisk -C none -a <span style="color: #993399">0x00808000</span> -e <span style="color: #993399">0x00808000</span> -n ramdisk -d ramfs<span style="color: #990000">.</span>gz ramfs-uboot<span style="color: #990000">.</span>img</tt></pre></div></div>
<div class="quoteblock">
<div class="content">
<div class="paragraph"><p>其中-a 和 -e分别是指定加载定制和执行地址</p></div>
</div>
<div class="attribution">
&#8212; cite source
</div></div>
<div class="paragraph"><p>而kernel的uboot版就不需要这么手动生成了，在编译kernel的时候，可以通过make uImage来制作uboot格式镜像，默认的加载地址是0x00008000，你也可以通过LOADADDR指定你自己的加载地址，这里用默认的。</p></div>
<div class="paragraph"><p>镜像准备好之后，需要把这两个镜像拷贝到一个指定的目录，这样在用tftp传输的时候，能够找到对应的镜像。这里假设拷贝到~/armsource/tftp目录下。</p></div>
<div class="paragraph"><p>下一步，我们需要交叉编译uboot。在编译之前，我们需要对uboot进行一些配置。由于我们使用的是versatilepb，它对应的配置文件在include/configs/versatile.h中，这里对这个文件的修改如下：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt><span style="font-weight: bold"><span style="color: #000080">#define</span></span> CONFIG_ARCH_VERSATILE_QEMU
<span style="font-weight: bold"><span style="color: #000080">#define</span></span> CONFIG_INITRD_TAG
<span style="font-weight: bold"><span style="color: #000080">#define</span></span> CONFIG_SYS_PROMPT  <span style="color: #FF0000">"myboard &gt; "</span>
<span style="font-weight: bold"><span style="color: #000080">#define</span></span> CONFIG_BOOTCOMMAND <span style="color: #990000">\</span>
  <span style="color: #FF0000">"sete ipaddr 10.0.2.15;"</span><span style="color: #990000">\</span>
  <span style="color: #FF0000">"sete serverip 10.0.2.2;"</span><span style="color: #990000">\</span>
  <span style="color: #FF0000">"set bootargs 'console=ttyAMA0,115200 root=/dev/mmcblk0';"</span><span style="color: #990000">\</span>
  <span style="color: #FF0000">"tftpboot 0x00007fc0 uImage;"</span><span style="color: #990000">\</span>
  <span style="color: #FF0000">"tftpboot 0x00807fc0 ramfs-uboot.img;"</span><span style="color: #990000">\</span>
  <span style="color: #FF0000">"bootm 0x7fc0 0x807fc0"</span></tt></pre></div></div>
<div class="quoteblock">
<div class="content">
<div class="paragraph"><p>其中ARCH_VERSATILE_QEMU是为了让uboot为了适应qemu做一些配置上的调整。
INITRD_TAG是让uboot通过tag_list给kernel传递initramfs的地址，如果没有这个配置选项，kernel是找不到uboot传给他的initramfs。
SYS_PROMPT是指定uboot的命令提示符，你可以指定你自己的名字。
BOOTCOMMAND是指定uboot起来后，自动执行的命令，这里是让uboot自动设置自己的ip和tftp服务器的ip，然后设定传递给kernel的参数，最后三个命令是把kernel镜像和initramfs镜像装载进来，并从内存指定地址开始执行指令。其实这些命令，也可以在uboot起来后，自己输入。</p></div>
</div>
<div class="attribution">
&#8212; cite source
</div></div>
<div class="admonitionblock">
<table><tr>
<td class="icon">
<div class="title">Note</div>
</td>
<td class="content">
<div class="paragraph"><p>注意：在设置uboot的ip的时候，一定要和qemu给定的ip对应。由于这里使用的qemu内部自带的tftp服务，所以这里的ip和qemu内部tftp服务器的ip在同一个网段。</p></div>
</td>
</tr></table>
</div>
<div class="paragraph"><p>uboot配置完之后，可以通过如下命令来编译uboot:</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>make versatilepb_config <span style="color: #009900">ARCH</span><span style="color: #990000">=</span>arm <span style="color: #009900">CROSS_COMPILE</span><span style="color: #990000">=</span>arm-none-linux-gnueabi-
make -j<span style="color: #993399">12</span> <span style="color: #009900">ARCH</span><span style="color: #990000">=</span>arm <span style="color: #009900">CROSS_COMPILE</span><span style="color: #990000">=</span>arm-none-linux-gnueabi-</tt></pre></div></div>
<div class="paragraph"><p>如果没什么错误，就会生成一个u-boot镜像，然后我们就可以通过qemu来加载它：</p></div>
<div class="listingblock">
<div class="content"><!-- Generator: GNU source-highlight 3.1.6
by Lorenzo Bettini
http://www.lorenzobettini.it
http://www.gnu.org/software/src-highlite -->
<pre><tt>sudo qemu-system-arm -M versatilepb -kernel u-boot -m 256M -net nic -net user<span style="color: #990000">,</span><span style="color: #009900">tftp</span><span style="color: #990000">=~</span>/armsource/tftp -sd hda<span style="color: #990000">.</span>img -nographic</tt></pre></div></div>
<div class="paragraph"><p>命令执行后，你就可以和之前一样的内核加载，最后经过两次跳转，到我们的sd卡上的文件系统。</p></div>
</div>
</div>
<div class="sect1">
<h2 id="_结语">结语</h2>
<div class="sectionbody">
<div class="paragraph"><p>到这里，我们最终完成了qemu&#8201;&#8212;&#8201;&gt; uboot -&#8594; kernel -&#8594; initramfs -&#8594; hda.img这一过程 <span class="footnote span9 pull-right"><br>[<a href="http://elinux.org/Virtual_Development_Board">http://elinux.org/Virtual_Development_Board</a>]<br></span> 。而这也是制作嵌入式系统，甚至一个桌面发行版本的基本流程。如果看完这篇文章后，还对嵌入式系统念念不忘，还是建议你买一块开发板，然后真真走一遍这个过程，毕竟这是用qemu模拟的。现在有很多open source hardware project(Arduino, Beagle Board, Cubieboard，Odroid，PandaBoard，Raspberry Pi)，你可以购买他们的板子，然后移植任何自己喜欢的东西。由于是open source，你可以获取到很多资料，并且有社区支持。</p></div>
</div>
</div>
</div>
<div id="footnotes"><hr></div>
</div> <!-- span9 content -->
</div> <!-- row-fluid -->
</div> <!-- container -->

<div id="footer" class="container">
<div id="footer-text" class="pull-right">
Created by Pingbo Wen, all rights reserved. <br />
Last updated 2013-10-14 15:05:58 CST
</div>
</div>
<script src="http://code.jquery.com/jquery.js"></script>
<script src="http://172.16.11.220:8080/js/bootstrap.min.js"></script>
<script>
$("#nav").load("http://172.16.11.220:8080/core/nav.inc");
</script>
</body>
</html>
