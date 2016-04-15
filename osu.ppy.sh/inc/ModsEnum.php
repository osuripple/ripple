<?php

// All the mods' values in any mods combination integer.
// Usage: $modInt & ModsEnum::ModToSeeIfIsInTheModsCombination
// If you don't really know what are these, lookup "bitwise operators php" on google.
class ModsEnum {
	const None = 0;
	const NoFail = 1;
	const Easy = 2;
	const NoVideo = 4;
	const Hidden = 8;
	const HardRock = 16;
	const SuddenDeath = 32;
	const DoubleTime = 64;
	const Relax = 128;
	const HalfTime = 256;
	const Nightcore = 512;
	const Flashlight = 1024;
	const Autoplay = 2048;
	const SpunOut = 4096;
	const Relax2 = 8192;
	const Perfect = 16384;
	const Key4 = 32768;
	const Key5 = 65536;
	const Key6 = 131072;
	const Key7 = 262144;
	const Key8 = 524288;
	const keyMod = 1015808;
	const FadeIn = 1048576;
	const Random = 2097152;
	const LastMod = 4194304;
	const Key9 = 16777216;
	const Key10 = 33554432;
	const Key1 = 67108864;
	const Key3 = 134217728;
	const Key2 = 268435456;
}
