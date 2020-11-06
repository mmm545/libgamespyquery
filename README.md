# libgamespyquery
A query virion for Pocketmine-MP\
This virion uses GS4 to query servers which provides more info. Servers that don't have GS4 supported/enabled can't be queried using this virion, so you need to use another virion such as [libpmquery](https://github.com/jasonwynn10/libpmquery)
## Usage
First you create a new GameSpyQuery instance, first argument is the IP address to query, second argument is the port to query
```php
$query = new GameSpyQuery("someserver.org", 19132);
```
Then we query the server
```php
$query->query();
```
You can set a timeout, but this argument is optional\
The query function will throw a GameSpyQueryException if the destination IP and port can't be queried, so you need to surround it with a try-catch block\
If everything worked correctly, you can use the `get()` function to get some info about the server\
List of the data you can get:
```php
$query->get("hostname"); // Server MOTD
$query->get("gametype"); // Game type, not sure what that means
$query->get("game_id"); // I think that's the game edition
$query->get("version"); // Version of minecraft the server is running on
$query->get("server_engine"); // Server software being used
$query->get("plugins"); // Plugins list with their version
$query->get("map"); // Current world
$query->get("numplayers"); // Number of online players
$query->get("maxplayers"); // Max number of players
$query->get("whitelist"); // On if whitelist is turned on, otherwise off
$query->get("hostip"); // Host ip
$query->get("hostport"); // Host port
$query->get("players"); // List of online players names
```
In case you want to get the raw response, you can use `getStatusRaw()`
