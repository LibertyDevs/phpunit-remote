phpunit-remote
==============

# こんな方に
- Vagrant+VirtualBoxなどを使って開発している
- Netbeansからphpunitを仮想マシン上で実行したい、カバレッジもみたい
- コマンド叩いてテスト実行するの面倒

# 注意点
- 開発環境にもphpは必要(sshも)です、phpunitは不要です
- Netbeansでもっと簡単にできるよ！というのがあれば是非教えてください

# 使い方
- ダウンロードしたスクリプトの**Config.php**を開き、user configuration に囲まれている箇所を変更します。
- ダウンロードしたスクリプトをNetbeansへ設定します
- [プロジェクト]を右クリック
- [カテゴリ内のphpunit]をクリック
- [カスタムPHPUnitスクリプトの使用]のみチェックをいれてダウンロードしたスクリプトを指定
- いつも通りテスト実行

```php/Config.php
    /**
     * Procject configuration
     */
    private $projectRootLocal = "/Users/liberty/works/virtual_machines/vm1/crawler";
    private $testRootLocal = "/Users/liberty/works/virtual_machines/vm1/crawler/app/tests";
    private $projectRootRemote = "/vagrant_data/crawler";
    private $testRootRemote = "/vagrant_data/crawler/app/tests";
    private $phpunitPathRemote = "/vagrant_data/crawler/vendor/bin/phpunit";
    private $phpunitConfigurationPathRemote = "/vagrant_data/crawler/phpunit.xml";
    private $phpunitBootstrapPathRemote = "/vagrant_data/crawler/bootstrap/autoload.php";

    /**
     * ssh2 configuration
     */
    private $hostRemote = 'vm1';
    private $portRemote = '2222';
    private $userRemote = 'vagrant';
    private $pubkey = '/Users/liberty/.vagrant.d/insecure_public_key';
    private $privkey = '/Users/liberty/.vagrant.d/insecure_private_key';

    /**
     * remote nb path configuration
     */
    private $nbSuitePathRemote = "/tmp/NetBeansSuite.php";

    //////////////////////////////////////////////////////
```