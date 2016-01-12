<?php
// Unused. There should be an integer in the database containing the various things an user uses for playing osu!, and apart from the usual ones, also a spoon, a leap motion controller, and an oculus rift.
class PlayStyleEnum {
  const Mouse = 1;
	const Tablet = 2;
	const Keyboard = 4;
	const TouchScreen = 8;
	const Spoon = 16; // Only if you are dellirium. https://www.youtube.com/watch?v=MWdQWCtmV3o
	const LeapMotion = 32; // Someone did, yes. https://www.youtube.com/watch?v=ZWjeyy1bQOY
	const OculusRift = 64; // https://www.youtube.com/watch?v=yMK2VFrrqv0
}
