<?php


namespace CsNle\PointCard;

//COMMON
use pocketmine\command\Command;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\CommandSender;
//For run cmd in cdk
use pocketmine\command\CommandReader;
use pocketmine\command\CommandExecuter;
use pocketmine\command\ConsoleCommandSender;
//NEED API
use RVIP\RVIP;
use RsFunction\RsFunction;
use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener
{
	public function PC_Version() {
		return '2';
	}

	public $options = [
		'enableVIP' => true,
		'enableEco' => true,
	];

//On Enable + Config Create.

    public function onEnable() {
		$this->getLogger()->info(TextFormat::GREEN . 'PointCard Version '.$this->PC_Version().' by CKylinMC');
	    $this->getServer()->getPluginManager()->registerEvents($this, $this);
		// $this->options['enableVIP'];
		// $this->options['enableEco'];
        if (!$this->getServer()->getPluginManager()->getPlugin('EconomyAPI')) {
			$this->options['enableEco'] = false;
			$this->getLogger()->info(TextFormat::RED . 'Please install EconomyAPI!');
		}
        if (!$this->getServer()->getPluginManager()->getPlugin('RVIP')) {
			$this->options['enableVIP'] = false;
			$this->getLogger()->info(TextFormat::RED . 'Please install RVIP!');
		}
		if($this->options['enableEco'] && $this->options['enableVIP']){
			$this->getLogger()->info(TextFormat::GREEN . 'RVIP and EconomyAPI have been detected. Everythings is OK.');
		}
            $this->path = $this->getDataFolder();
		@mkdir($this->path);@mkdir($this->path);
		$this->cfg = new Config($this->path."config.yml", Config::YAML,array());
		$this->cmds = new Config($this->path."cmds.yml", Config::YAML,array(
			'broadcast'=>'say %p opened a point card!',
		));
		
		if(!$this->cfg->exists("EXAMPLE"))
		{
			$this->cfg->set("EXAMPLE",array("is_used"=>"false//是否被领取","vip_level"=>"3//VIP等级","vip_days"=>"10//VIP天数增加","point"=>"3000//points增加","money"=>"300000//钱数增加","prefix"=>"Example//获得的头衔,.(英文点号)表示不更改","cmd"=>"false//执行的指令预设(console身份)使用false代表无动作(以上所有//以及后面内容在真实填写时都不要填写)"));
			$this->cfg->save();
			
             $this->getLogger()->info("Please set config file as example.");
		}
		if(!$this->cfg->exists("hFngJbgKBf"))
		{
			$this->cfg->set("hFngJbgKBf",array("is_used"=>"示例玩家","vip_level"=>"2","vip_days"=>"20","point"=>"90000","money"=>"90000","prefix"=>"杀灭狂魔","cmd"=>"broadcast"));
			$this->cfg->save();
		}
		
		//Lang file will be added in some times.
			$this->lang = new Config($this->getDataFolder() . 'lang.yml', Config::YAML, yaml_parse(stream_get_contents($resource = $this->getResource('lang.yml'))));
			@fclose($resource);
			// $langname = $this->lang->get('langname');
			// $langauthor = $this->lang->get('langauthor');
			$langname = $this->getlang('langname');
			$langauthor = $this->getlang('langauthor');
			$langver = $this->getlang('langversion');
            $this->getLogger()->info("Loaded lang file for ".$langname." by ".$langauthor);
			if($langver!=$this->PC_Version()){
				$this->getLogger()->info(TextFormat::RED . 'Lang file version is not same as PointCard version, may cause some error.');
			}
            $this->saveDefaultConfig();
            $this->reloadConfig();
			
			$this->vip = RVIP::$RVIP;

            
	}
	
	public function onDisable() {
		$this->saveDefaultConfig();
		$this->getLogger()->info(TextFormat::BLUE . $this->getlang('saved-and-exited'));
	}
	
	public function onCommand(CommandSender $s, Command $cmd, $label, array $args) {
		if($cmd=="pc") {
			if($s instanceof Player) {
				if(isset($args[0])) {
					if($this->cfg->exists($args[0])) {
						$key = $args[0];
						$cdk = $this->cfg->get($key);
						//兼容旧版
						if(!isset($cdk['remain']))
						{
							if($cdk['is_used']==='false'){
								$cdk['remain'] = -2;
							}else{
								$cdk['remain'] = -3;
							}
						}
						if($cdk['is_used']==='false' && ($cdk['remain']>0 || $cdk['remain']==-2)){
							$this->sendlang('title',$s);
							$s->sendMessage('+ '.$this->getlang('cdk').': '.$key);
							$s->sendMessage('+ '.$this->getlang('stat').'：');
							if($this->options['enableEco']){
								if(!empty($cdk['money'])){
									EconomyAPI::getInstance()->addMoney($s,$cdk['money']);
									$s->sendMessage('+ '.$cdk['money'].$this->getlang('add-money').$s->getName().$this->getlang('ones-account'));
								}
							}
							if($this->options['enableVIP']){
								if(!empty($cdk['point'])){
									$this->vip->Point('add',$s->getName(),$cdk['point']);
									$s->sendMessage('+ '.$cdk['point'].$this->getlang('add-point').$s->getName().$this->getlang('ones-account'));
								}
								if((!empty($cdk['vip_days'])) && (!empty($cdk['vip_level']))){
									if($cdk['vip_days']>=1 && $cdk['vip_level']>=1){
										// $this->vip->VIP('add',$s->getName(),$cdk['vip_level'],$cdk['vip_days']);
										// $s->sendMessage('+ '.$s->getName().$this->getlang('add-days').$cdk['vip'].$this->getlang('day'));
										$return_code = $this->addVip($s->getName(),$cdk['vip_level'],$cdk['vip_days']);
										switch($return_code){
											case 0:
												$this->sendlang('vip-add-same',$s);
												break;
											case 4:
												$this->sendlang('vip-add-level-unknow',$s);
												break;
											case 3:
												$this->sendlang('vip-add-big',$s);
												break;
											case 2:
												$this->sendlang('vip-add-small',$s);
												break;
											case 1:
												$this->sendlang('vip-add-param-failed',$s);
												break;
										}
									}
								}
								//兼容旧版配置
								if(!empty($cdk['vip'])){
									$cdk['vip_days'] = $cdk['vip'];
									$cdk['vip_level'] = 1;
									if($cdk['vip_days']>=1 && $cdk['vip_level']>=1){
										// $this->vip->VIP('add',$s->getName(),$cdk['vip_level'],$cdk['vip_days']);
										// $s->sendMessage('+ '.$s->getName().$this->getlang('add-days').$cdk['vip'].$this->getlang('day'));
										$return_code = $this->addVip($s->getName(),$cdk['vip_level'],$cdk['vip_days']);
										switch($return_code){
											case 0:
												$this->sendlang('vip-add-same',$s);
												break;
											case 4:
												$this->sendlang('vip-add-level-unknow',$s);
												break;
											case 3:
												$this->sendlang('vip-add-big',$s);
												break;
											case 2:
												$this->sendlang('vip-add-small',$s);
												break;
											case 1:
												$this->sendlang('vip-add-param-failed',$s);
												break;
										}
									}
								}
								if((!empty($cdk['prefix']))&&($cdk['prefix']!='false')){
									$this->vip->Prefix('add',$s->getName(),$cdk['prefix']);
									$this->msgALL($s->getName().$this->getlang('get-Prefix').$cdk['prefix'],true);
								}
							}
							$cdk['remain']--;
							if(!empty($cdk['cmd'])){
								$name = $s->getName();
								$if = $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $this->parseCmdPresets($cdk['cmd'],$s,$key,$cdk));
							}
							$cdk['is_used'] = $this->getlang('player').$s->getName();
							$this->cfg->set(
								$key,
								$cdk
							);
							$this->cfg->save();
							$this->reloadConfig();
							$this->sendlang('use-completely',$s);
						} elseif($cdk['remain']==-3) {
							$this->sendlang('title',$s);
							$s->sendMessage('+ '.$this->getlang('cdk').': '.$key);
							$s->sendMessage('+ '.$this->getlang('stat'));
							$s->sendMessage('+ '.$this->getlang('has-got-by').$cdk['is_used']);
						}elseif($cdk['remain']==0) {
							$this->sendlang('title',$s);
							$s->sendMessage('+ '.$this->getlang('cdk').': '.$key);
							$s->sendMessage('+ '.$this->getlang('stat'));
							$s->sendMessage('+ '.$this->getlang('has-got'));
						}else{
							$s->sendMessage('+ '.$this->getlang('has-got-error')."({$cdk['remain']}-{$key})");
						}
					} else {
						$this->sendlang('Unknow-cdk',$s);
						return true;
					}
				} else {
					$this->sendlang('pc-usage-simple',$s);
				}
			} else {
				$this->sendlang('run-in-game',$s);
			}
			return true;
		}

		if($cmd=="nothingtodo"){
			return true;
		}

		if($cmd=="vipinfo"){
			// if(empty($args[0])) return false;
			if(!$s instanceof Player){
				$this->sendlang('run-in-game',$s);
				return true;
			}
			if($s->isOp()){
				if(empty($args[0])) {
					$this->printVIPmsg($s->getName(),$s);
				}else{
					$this->printVIPmsg($args[0],$s);
				}
			}else{
				$this->printVIPmsg($s->getName(),$s);
				if(!empty($args[0])){
					$this->sendlang('vip-check-perms-denied',$s);
				}
			}
			return true;
		}

		if($cmd=="pcget") {
			if($s->isOp()){
				if(empty($args[0])) return false;
				if($this->cfg->exists($args[0])){
					$this->sendlang("cdkinfotitle",$s);
					$this->printCDKmsg($args[0],$s);
					return true;
				} else {
					$this->sendlang("Unknow-cdk",$s);
				}
			} else {
				$this->sendlang("perms-denied",$s);
				return;
			}
		}

		if($cmd=="pcreset") {
			if($s->isOp()){
				if(empty($args[0])) return false;
				if($this->cfg->exists($args[0])){
					$this->resetCDK($args[0]);
					$this->sendlang("cdkinfotitle",$s);
					$this->printCDKmsg($args[0],$s);
					return true;
				} else {
					$this->sendlang("Unknow-cdk",$s);
				}
			} else {
				$this->sendlang("perms-denied",$s);
				return;
			}
		}
		//MARK: pcgen
		if($cmd=="pcgen") {
			if(count($args)<1) {
				$s->sendMessage('Usage /pcgen (options)');
			} else {
				if($s->isOp()){
					$modified = false;
					$front = '';
					$send = false;
					$remain = -2;
					$count = 5;
					$level = 0;
					$days = 0;
					$point = 0;
					$money = 0;
					$prefix = '';
					$msg = false;//TODO
					$cmd = false;
					foreach($args as $a){
						// $this->msgALL($a,true);
						$r = $this->explodeCmds($a);
						if(!$r===false){
							if($r['checked']){
								$mode = $r[0];
								$value = $r[1];
								switch($mode){
									case 'VIP':
										$modified = true;
										$level = $value;
										break;
									case 'POINT':
										$modified = true;
										$point = $value;
										break;
									case 'DAY':
										$modified = true;
										$days = $value;
										break;
									case 'MONEY':
										$modified = true;
										$money = $value;
										break;
									case 'PREFIX':
										$modified = true;
										$prefix = $value;
										break;
									case 'CMD':
										$modified = true;
										$cmd = $value;
										break;
									case 'COUNT':
										// $modified = true;
										if($value>=61) break;
										if($value<=0) break;
										$count = $value;
										break;
									case 'FRONT':
										// $modified = true;
										$front = $value;
										break;
									case 'SEND':
										// $modified = true;
										$send = $value;
										break;
									case 'REMAIN':
										// $modified = true;
										if($value<=0) break;
										$remain = $value;
										break;
								}
							}
						}
					}
					if($modified===false){
						$this->sendlang('please-defined-an-option-at-least',$s);
						return false;
					}
					$cdkcfg = array(
						'is_used'=>'false',
						'remain'=>$remain,
						'vip_days'=>$days,
						'vip_level'=>$level,
						'point'=>$point,
						'money'=>$money,
						'prefix'=>$prefix,
						'cmd'=>$cmd
					);
					if(!empty($send)){
						$this->msgALL($this->getlang('title'),true);
						$this->msgALL($this->getlang('cdk-send-1'),true);
					}
					for ($x=1; $x<=$count; $x++){
						$cdkey = $this->generator($front);
						$this->cfg->set($cdkey,$cdkcfg);
						$s->sendMessage('+ '.$this->getlang("cdk").':'.$cdkey.$this->getlang('Generated'));
						$this->printCDKmsg($cdkey,$s);
						if(!empty($send)){
							$this->msgALL('+ CDK: '.$cdkey);
						}
					}
					if(!empty($send)){
						$this->msgALL($this->getlang('cdk-send-2'),true);
					}

					$this->cfg->save();
					$this->reloadConfig();
				} else {
					// $s->sendMessage('权限不足');
					$this->getLogger()->info(TextFormat::RED . $s->getName() . $this->getlang('someone-is-trying-to-gen-a-cdk-but-failed-cause-perms-denied'));
				}
				return true;
			}
		}
		
		if($cmd=="pcmgr"){
			if(!isset($args[0])){
				$s->sendMessage('Usage /pcmgr help');
				return true;
			}
			if(!$s->isOp()) {
				$s->sendMessage($this->getlang('permission-denied'));
				return true;
			}
			$cmdp = $args[0];
				$all = $this->cfg->getAll();
			//TODO
			if($cmdp=='ls'){
				$s->sendMessage('Please use "/pcget" instead.');
				return true;
			} elseif($cmdp=='del') {
				if(!isset($args[1])){
					$s->sendMessage('Usage：/pcmgr del <cdk>');
					return true;
				}
				if(!$this->cfg->exists($args[1])){
					$s->sendMessage($this->getlang('Unknow-cdk'));
					return true;
				}
				$this->cfg->remove($args[1]);
				$s->sendMessage('CDK-'.$args[1].$this->getlang('Removed'));
				$this->reloadcfg();
				return true;
			} elseif($cmdp=='edit'){
				if(count($args)<4){
					$s->sendMessage('Usage： /pcmgr edit <cdk> <prj> <obj>');
					$s->sendMessage('prj: vip/point/money/prefix');
					return true;
				}
				if(!$this->cfg->exists($args[1])){
					$s->sendMessage($this->getlang('Unknow-cdk'));
					return true;
				}
				$prj = $args[2];
				$obj = $args[3];
				if($prj=='vip') {
					if(!is_numeric($obj)) {
						$s->sendMessage($this->getlang('obj-error'));
						return true;
					}
					$get = $this->cfg->get($prj);
					$this->cfg->set($args[1],array('is_used'=>'false','vip'=>$obj,'point'=>$all[$args[1]]['point'],'money'=>$all[$args[1]]['money'],'prefix'=>$all[$args[1]]['prefix'],'cmd'=>'false'));
					$s->sendMessage($this->getlang('Changed').$args[1]);
					$this->printCDKmsg($args[1],$s);
				$this->reloadcfg();
					return true;
				}
				if($prj=='point') {
					if(!is_numeric($obj)) {
						$s->sendMessage($this->getlang('obj-error'));
						return true;
					}
					$get = $this->cfg->get($prj);
					$this->cfg->set($args[1],array('is_used'=>'false','vip'=>$all[$args[1]]['vip'],'point'=>$obj,'money'=>$all[$args[1]]['money'],'prefix'=>$all[$args[1]]['prefix'],'cmd'=>'false'));
					$s->sendMessage($this->getlang('Changed').$args[1]);
					$this->printCDKmsg($args[1],$s);
				$this->reloadcfg();
					return true;
				}
				if($prj=='money') {
					if(!is_numeric($obj)) {
						$s->sendMessage($this->getlang('obj-error'));
						return true;
					}
					$get = $this->cfg->get($prj);
					$this->cfg->set($args[1],array('is_used'=>'false','vip'=>$all[$args[1]]['vip'],'point'=>$all[$args[1]]['point'],'money'=>$obj,'prefix'=>$all[$args[1]]['prefix'],'cmd'=>'false'));
					$s->sendMessage($this->getlang('Changed').$args[1]);
					$this->printCDKmsg($args[1],$s);
				$this->reloadcfg();
					return true;
				}
				if($prj=='prefix') {
					$get = $this->cfg->get($args[1]);
					$this->cfg->set($args[1],array('is_used'=>'false','vip'=>$all[$args[1]]['vip'],'point'=>$all[$args[1]]['point'],'money'=>$all[$args[1]]['money'],'prefix'=>$obj,'cmd'=>'false'));
					$s->sendMessage($this->getlang('Changed').$args[1]);
					$this->printCDKmsg($args[1],$s);
					return true;
				} else {
					$s->sendMessage($this->getlang('obj-unknow'));
					$s->sendMessage('obj:vip/point/money/prefix');
					return true;
				}
			} elseif ($cmdp=='help'){
				$s->sendMessage('=-=-=|help|=-=-=');
				$s->sendMessage('/pcmgr <cmd> <part> <part> <part>');
				$s->sendMessage('/pcmgr ls');
				$s->sendMessage('/pcmgr del <cdk>');
				$s->sendMessage('/pcmgr edit <cdk> <obj> <value>');
				$s->sendMessage('obj:vip/point/money/prefix');
				$s->sendMessage('/pcgen (<front>) <vip> <point> <money> <prefix>');
				return true;
			} else {
				$s->sendMessage('Unknow cmd,type "/pcmgr help" for help');
				return true;
			}
		}
		
	}
	
	public function getCDK($cdk) {//instead getCDKmsg
		$cdks = $this->cfg->getAll();
		if(!isset($cdks[$cdk])) {
			return $this->getlang('Unknow-cdk');
		} else {
			$cdkinfo = $cdk;
			// $cdkinfo = $this->getlang('cdkinfotitle');
			if($cdks[$cdk]['is_used']!=='false') {
				$cdkinfo .= ' | '.$this->getlang('used-player').': '.$cdks[$cdk]['is_used'];
			}
			$vipdays = $cdks[$cdk]['vip_days'];
			$viplevel = $cdks[$cdk]['vip_level'];
			$point = $cdks[$cdk]['point'];
			$money = $cdks[$cdk]['money'];
			$count = $cdks[$cdk]['remain']<0 ? $this->getlang('single-time') : $cdks[$cdk]['remain'];
			$prefix = empty($cdks[$cdk]['prefix']) ? '无称号' : $cdks[$cdk]['prefix'];
			$cdkinfo .= ' | '.$this->getlang('vipdays').': '.$vipdays;
			$cdkinfo .= ' | '.$this->getlang('viplevel').': '.$viplevel;
			$cdkinfo .= ' | '.$this->getlang('points').': '.$point;
			$cdkinfo .= ' | '.$this->getlang('money').': '.$money;
			$cdkinfo .= ' | '.$this->getlang('prefix').': '.$prefix;
			$cdkinfo .= ' | '.$this->getlang('count').': '.$count;
			return $cdkinfo;
		}
	}
	
	public function printCDKmsg($cdk,$p) {//instead printCDKmsg
		$info = $this->getCDK($cdk);
		$p->sendMessage($info);
	}
	
	//API
	public function msgALL($msg,$console = true) {//broadcast
	if(!isset($msg)) { return false; }
	$allp = $this->getServer()->getOnlinePlayers();
	foreach($allp as $p){
			$p->sendMessage($msg);
		}
	
	if($console){
		$this->getLogger()->info($msg);
	}
}
//I use new function instead of follows function to print cdk info.
	public function generator($prefix = "") {
		$lib = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			//$this->getLogger()->info(TextFormat::RED . '1');
		shuffle($lib);
			//$this->getLogger()->info(TextFormat::RED . '2');
		$return = $prefix.$lib[0].$lib[1].$lib[2].$lib[3].$lib[4].$lib[5].$lib[6].$lib[7].$lib[8].$lib[9];
			//$this->getLogger()->info(TextFormat::RED . '3');
		return $return;
	}
	
	
	public function getlang($text) {
		$langs = $this->lang->getAll();
		if(!isset($langs[$text])) {
			$this->getLogger()->warning(TextFormat::BLUE . 'Lang file error:Unknow langfile object - '.$text);
			return '{{'.$text.'}}';
		}
		return $langs[$text];
	}
	
	public function sendlang($text,$p) {
		$msg = $this->getlang($text);
		$p->sendMessage($msg);
	}
	
	public function reloadcfg() {
		$this->cfg->save();
            $this->saveDefaultConfig();
            $this->reloadConfig();
	}
	
	// Follows api is some basic apis.Ready for something.
	
	public function getPoints($p) {
		return $this->vip->Point("get",$p);
	}
	
	public function getVIPdays($p) {
		return $this->vip->Day("get",$p);
	}
	
	public function getPrefix($p) {
		return $this->vip->Prefix("get",$p);
	}

    public function explodeCmds($cmd){
		// $this->msgALL($cmd,true);
        if(empty($cmd)) return false;
        $p = explode('=',$cmd);
        if(count($p)!==2) return false;
        $key = strtolower($p[0]);
        $value = $p[1];
        $result = $this->parseCmds($key,$value);
        if($result===false) return array($key,$value,'checked'=>false);
        return $result;
    }
    //MARK: parseCmds
    public function parseCmds($key,$value){
        // $mode;
        $result = array();
        switch($key){
            case 'vipdengji':
            case 'viplevel':
            case 'dengji':
            case 'levels':
            case 'level':
            case 'vip':
            case 'v':
                // $mode = 'VIP';
                if(!is_numeric($value)){
                    return false;
                }
                $result = array('VIP',(int) $value,'checked'=>true);
                break;
            case 'viptianshu':
            case 'tianshu':
            case 'vipdays':
            case 'vipday':
            case 'tian':
            case 'days':
            case 'day':
            case 'd':
                if(!is_numeric($value)){
                    return false;
                }
                $result = array('DAY',(int) $value,'checked'=>true);
                break;
            case 'dianquan':
            case 'dianshu':
            case 'points':
            case 'point':
            case 'dian':
            case 'p':
                if(!is_numeric($value)){
                    return false;
                }
                $result = array('POINT',(int) $value,'checked'=>true);
                break;
            case 'money':
            case 'qian':
            case 'm':
                if(!is_numeric($value)){
                    return false;
                }
                $result = array('MONEY',(int) $value,'checked'=>true);
                break;
            case 'messages':
            case 'message':
            case 'xiaoxi':
            case 'liuyan':
            case 'msg':
                if(!is_numeric($value)){
                    return false;
                }
                $result = array('MSG',(int) $value,'checked'=>true);
                break;
			case 'commands':
			case 'mingling':
			case 'command':
			case 'zhiling':
			case 'cmds':
			case 'run':
			case 'cmd':
			case 'c':
				$result = array('CMD',$value,'checked'=>true);
				break;
			case 'chenghao':
			case 'touxian':
			case 'prefix':
			case 'pf':
				if($value=='.') $value = '';
                $result = array('PREFIX',$value,'checked'=>true);
                break;
			case 'cdkprefix':
			case 'front':
			case 'f':
				$result = array('FRONT',$value,'checked'=>true);
				break;
			case 'shuliang':
			case 'numbers':
			case 'counts':
			case 'number':
			case 'count':
                if(!is_numeric($value)){
                    return false;
                }
                $result = array('COUNT',(int) $value,'checked'=>true);
                break;
			case 'broadcast':
			case 'hongbao':
			case 'send':
			case 's':
				$send = empty($value) ? false : true;
                $result = array('SEND',$send,'checked'=>true);
                break;
			case 'remain':
			case 'times':
			case 'time':
			case 'r':
                if((!is_numeric($value))){
                    return false;
                }
                $result = array('REMAIN',(int) $value,'checked'=>true);
                break;
            default:
                $result = false;
        }
        return $result;
    }

	public function resetCDK($cdk){
		if($this->cfg->exists($cdk)){
			$c = $this->cfg->get($cdk);
			$c['is_used'] = 'false';
			$this->cfg->set($cdk,$c);
			$this->reloadcfg();
			$this->getLogger()->info(TextFormat::GREEN . $this->getlang('cdk-reopened').$cdk);
			return true;
		}else{
			return false;
		}
	}
	
	public function isVip($Player,$boolean = true){
	    if(!empty($Player)) return false;
	    $res = $this->vip->VIP("get",$Player);
	    $vaildvip = array(1,2,3);
	    if($boolean===false) return $res;
	    if(in_array($res,$vaildvip)){
	        return true;
	    } else {
	        return false;
	    }
	}

	public function addVip($player,$level,$days){
		if(empty($player) || empty($level) || empty($days)){
			return 1;//Wrongly
		}
		$orgday = $this->vip->DAY('get',$player);
		$orgvip = $this->isVip($player,false);
		switch($orgvip){
			case 3:
				//
				if($level==$orgvip){
					$this->vip->VIP('add',$player,$level,$days+$orgday);
					return 0;// Success 3-3-3
				}else{
					$this->vip->VIP('add',$player,$orgvip,$days+$orgday);
					return 2;// small-? Failed
				}
				break;
			case 2:
				//
				if($level>$orgvip){
					$this->vip->VIP('add',$player,$level,($orgday / 2)+$days);
					return 3;// Success 2-3-2
				}elseif($level<$orgvip){
					$this->vip->VIP('add',$player,$orgvip,$days+$orgday);
					return 2;
				}else{
					$this->vip->VIP('add',$player,$level,$days+$orgday);
					return 0;// Success 2-2-2
				}
				break;
			case 1:
				//
				if($level>$orgvip){
					$this->vip->VIP('add',$player,$level,($orgday / 2)+$days);
					return 3;// Success 1-big-1
				}elseif($level<$orgvip){
					$this->vip->VIP('add',$player,$orgvip,$days+$orgday);
					return 2;
				}else{
					$this->vip->VIP('add',$player,$level,$days+$orgday);
					return 0;// Success 1-1-1
				}
				break;
			case 0:
				//
				$this->vip->VIP('add',$player,$level,$days);
				return 0;// Success ?-0-?
				break;
			default:
				//
				return 4;
		}
	}

	public function printVIPmsg($target,Player $p){
		$print = [$this->getlang('vipinfotitle'),$this->getlang('vipinfo-target').$target];
		if($this->isVip($target)){
			$lv = $this->vip->VIP('get',$target);
			$days = $this->DAY('get',$target);
			array_push($print,$this->getlang('vipinfo-lv').$lv,$this->getlang('vipinfo-days').$days);
			if($days<='7'){
				array_push($print,$this->getlang('vipinfo-warn'));
			}
		}else{
			array_push($print,$this->getlang('vipinfo-notvip'));
		}
		foreach($print as $line){
			$p->sendMessage($line);
		}
	}

	public function parseCmdPresets($key,Player $p,$cdk,$cdkinfo){
		if(empty($key)||empty($p)||empty($cdk)||empty($cdkinfo)) return 'nothingtofo';
		if(!$this->cmds->exists($key)){
			return 'nothingtodo';
		}else{
			$pn = $p->getName();
			$remain = $cdkinfo['remain']<0 ? $this->getlang('single-time') : $cdkinfo['remain'];
			$cmd = $this->cmds->get($key);
			$cmd = str_replace('%p',$pn,$cmd);
			$cmd = str_replace('%c',$cdk,$cmd);
			$cmd = str_replace('%d',$cdkinfo['vip_days'],$cmd);
			$cmd = str_replace('%l',$cdkinfo['vip_level'],$cmd);
			$cmd = str_replace('%m',$cdkinfo['money'],$cmd);
			$cmd = str_replace('%o',$cdkinfo['point'],$cmd);
			$cmd = str_replace('%f',$cdkinfo['prefix'],$cmd);
			$cmd = str_replace('%t',$remain,$cmd);
			return $cmd;
		}
	}

}