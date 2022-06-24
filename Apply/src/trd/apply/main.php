<?php

namespace trd\apply;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class main extends PluginBase implements Listener{

    public $c;
    public $f;
    public $a;
    public $tar = [];

    protected function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        $this->saveResource("form.yml");
        $this->saveResource("language.yml");
        $this->c = new Config($this->getDataFolder()."language.yml", 2);
        $this->f = new Config($this->getDataFolder()."form.yml", 2);
        $this->a = new Config($this->getDataFolder()."applys.yml", 2);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        if($command->getName() == "apply"){
            if(isset($args[0])){
                if($args[0] == "edit"){
                    if(isset($args[1])){
                        if ($this->a->exists($args[1])){
                            if($sender->hasPermission("apply.command.sup")){
                                $this->tar[$sender->getName()] = $args[1];
                                $this->EditForm($sender);
                            }
                        }
                    }
                }
            }else{
                $this->ApplyForm($sender);
            }
        }
        return true;
    }

    public function ApplyForm($player) {
        $form = new CustomForm(function (Player $player, $data = null){
            if($data === null){
                return;
            }

            $dc = $data[0];
            $rn = $data[1];
            $age = $data[2];
            $job = $data[3];
            $apply = $data[4];

            $this->a->setNested("{$player->getName()}.dc", $dc);
            $this->a->setNested("{$player->getName()}.rn", $rn);
            $this->a->setNested("{$player->getName()}.age",$age);
            $this->a->setNested("{$player->getName()}.job",$job);
            $this->a->setNested("{$player->getName()}.message",$apply);
            $this->a->save();
            $this->a->reload();
            $player->sendMessage($this->c->get("you.sent.an.apply"));
            foreach ($this->getServer()->getOnlinePlayers() as $players){
                if($players->hasPermission("apply.command.edit")){
                    $msg = str_replace("{NAME}", $player->getName(), $this->c->get("sent.apply"));
                    $players->sendMessage($msg);
                }
            }


        });
        $form->setTitle($this->f->get("form.apply.title")); // 0
        $form->addInput($this->f->get("form.apply.discord")); // 1 DISCORD NAME
        $form->addInput($this->f->get("form.apply.realname")); // 2 Reallife Name
        $form->addInput($this->f->get("form.apply.age")); // 2 AGE
        $form->addInput($this->f->get("form.apply.job")); // 3 what he want to be
        $form->addInput($this->f->get("form.apply.message")); // 4 His apply message
        $player->sendForm($form);

    }

    public function EditForm($player){
        $form = new CustomForm(function (Player $player, $data = null){
            if($data === null){
                unset($this->tar[$player->getName()]);
                return;
            }
            $ta = $this->tar[$player->getName()];
            $target = $this->getServer()->getPlayerByPrefix($ta);
            switch ($data[6]){
                case false:
                    return;
                case true:
                    if($target instanceof Player) {
                        $this->a->removeNested($target->getName());
                        $this->a->save();
                        $this->a->reload();
                        unset($this->tar[$player->getName()]);
                    }
            }
        });
        $ta = $this->tar[$player->getName()];
        $target = $this->getServer()->getPlayerByPrefix($ta);
        if($target instanceof Player) {
            $title = str_replace("{TARGET}", $target->getName(), $this->f->get("form.edit.title"));
            $form->setTitle($title);
            $form->addLabel($this->f->get("form.edit.information"));
            $form->addLabel("Discord Tag:\n{$this->a->getNested("{$target->getName()}.dc")}");
            $form->addLabel("RealName:\n{$this->a->getNested("{$target->getName()}.rn")}");
            $form->addLabel("Age:\n{$this->a->getNested("{$target->getName()}.age")}");
            $form->addLabel("Selected Job:\n{$this->a->getNested("{$target->getName()}.job")}");
            $form->addLabel("Apply Message:\n{$this->a->getNested("{$target->getName()}.message")}");
            $form->addToggle("delete", false);
            $player->sendForm($form);
        }

    }
}