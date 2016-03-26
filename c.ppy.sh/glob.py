"""Global objects and variables"""

import tokenList
import channelList
import matchList
import slotStatuses

db = None
conf = None
banchoConf = None
tokens = tokenList.tokenList()
channels = channelList.channelList()
matches = matchList.matchList()

matches.createMatch("test", "", 0, "nice beatmap", "somemd5here", 0, 999)
matches.matches[1].slots[0]["userID"] = 999
matches.matches[1].slots[0]["status"] = slotStatuses.notReady
matches.matches[1].matchPassword = "meme"
