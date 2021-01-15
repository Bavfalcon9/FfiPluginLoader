<?php

namespace SOFe\FfiPluginLoader;

use FFI;
use pocketmine\plugin\{PluginLoader, PluginDescription};
use pocketmine\utils\Utils;
use pocketmine\VersionInfo;


final class FfiPluginLoader implements PluginLoader {
	private static $pool = [];

	public static function loadLib(string $path) : FFI {
		$path = realpath($path);
		if(isset(self::$pool[$path])) {
			return self::$pool[$path];
		}
		$ffi = FFI::cdef(file_get_contents(__DIR__ . "/manifest.h"), $path);
		self::$pool[$path] = $ffi;
		return $ffi;
	}

	public function canLoadPlugin(string $path) : bool {
		$os = Utils::getOs();
		if ($os === "win") {
			return substr($path, -4) === ".dll";
		} else if ($os === "linux" || $os === "mac" || $os === "android") {
			return substr($path, -3) === ".so";
		} else {
			return false;
		}
	}

	public function getPluginDescription(string $path) : ?PluginDescription {
		$ffi = self::loadLib($path);
		$name = FFI::string($ffi->plugin_name());
		$version = FFI::string($ffi->plugin_version());
		$api = FFI::string($ffi->plugin_ffi_version());
		// TODO check $api against $this->getDescription()->getVersion()
		return new PluginDescription([
			"name" => $name,
			"version" => $version,
			"api" => [VersionInfo::BASE_VERSION],
			"main" => FfiPluginBase::class,
		]);
	}

	public function loadPlugin(string $path) : void {
		self::loadDynLib($path);
	}

	public function getAccessProtocol() : string {
		return "";
	}
}
