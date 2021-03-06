<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection SpellCheckingInspection
 * @noinspection PhpDocMissingReturnTagInspection
 * @noinspection PhpDocSignatureInspection
 */

declare(strict_types=1);

namespace kim\present\statusviewer;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Process;
use pocketmine\utils\TextFormat;

use function count;
use function file_exists;
use function mkdir;
use function number_format;
use function phpversion;
use function round;

final class Loader extends PluginBase{
    protected function onEnable() : void{
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
            $server = $this->getServer();

            /** @var string|null $statusMessage */
            $statusMessage = null;

            foreach($server->getOnlinePlayers() as $player){
                if($this->isViewer($player)){
                    if($statusMessage === null){
                        $threadCount = Process::getThreadCount();
                        $totalMemory = number_format(round((Process::getAdvancedMemoryUsage()[1] / 1024) / 1024, 2), 2);

                        $worldCount = 0;
                        $chunkCount = 0;
                        $entityCount = 0;
                        $tileCount = 0;
                        foreach($server->getWorldManager()->getWorlds() as $world){
                            ++$worldCount;
                            foreach($world->getChunks() as $chunk){
                                ++$chunkCount;

                                $entityCount += count($chunk->getEntities());
                                $tileCount += count($chunk->getTiles());
                            }
                            $chunkCount += count($world->getChunks());
                        }
                        $statusMessage =
                            "Server: {$server->getName()}_v{$server->getApiVersion()} (PHP " . phpversion() . ")\n" .
                            "TPS: {$server->getTicksPerSecond()} ({$server->getTickUsage()}%)\n" .
                            "Threads: {$threadCount}, Memory: {$totalMemory} MB\n" .
                            "World({$worldCount}) Chunk: {$chunkCount}, Entity: {$entityCount}, Tile: {$tileCount}";
                    }

                    $player->sendTip($statusMessage);
                }
            }
        }), 2);
    }

    protected function onDisable() : void{
        $dataFolder = $this->getDataFolder();
        if(!file_exists($dataFolder)){
            mkdir($dataFolder);
        }
        $this->getConfig()->save();
    }

    /** @param string[] $args */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "It can only be used in-game");
            return true;
        }

        if($this->isViewer($sender)){
            $this->removeViewer($sender);
            $sender->sendMessage(TextFormat::AQUA . "[StatusViewer] Disable status viewer");
        }else{
            $this->addViewer($sender);
            $sender->sendMessage(TextFormat::AQUA . "[StatusViewer] Enable status viewer");
        }
        return true;
    }

    public function isViewer(Player $player) : bool{
        return (bool) $this->getConfig()->get($player->getXuid(), false);
    }

    public function addViewer(Player $player) : void{
        $this->getConfig()->set($player->getXuid(), true);
    }

    public function removeViewer(Player $player) : void{
        $this->getConfig()->remove($player->getXuid());
    }
}