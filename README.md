# libgamespyquery
A query virion for Pocketmine-MP\
This virion uses GS4 to query servers which provides more info. Servers that don't have GS4 supported/enabled can't be queried using this virion, so you need to use another virion such as [libpmquery](https://github.com/jasonwynn10/libpmquery)
## Usage
To query a server, you call the `GameSpyQuery::query()` function, first argument is the IP, second argument is the port
```php
$query = GameSpyQuery::query("someserver.org", 19132);
```
There's an additional (optional) third argument which is the timeout, it's simply how long it will wait for a response before it closes the connection. The timeout is in seconds
```php
$query = GameSpyQuery::query("someserver.org", 19132, 5); // it will wait for 5 seconds, if there was no response it would close the connection
```
After the query is done, it will return a `GameSpyQuery` instance

The `query()` function will throw a GameSpyQueryException if the destination IP and port can't be queried, so you need to surround it with a try-catch block

After that, use the `get()` function to get info about the server. List of all the data you can get:
```php
$query->get("hostname"); // Server MOTD
$query->get("gametype"); // Game type, e.g SMP or CMP
$query->get("game_id"); // Game edition
$query->get("version"); // Version of minecraft the server is running on
$query->get("server_engine"); // Server software being used
$query->get("plugins"); // Plugins list with their version
$query->get("map"); // Current world
$query->get("numplayers"); // Number of online players
$query->get("maxplayers"); // Max number of players
$query->get("whitelist"); // On if whitelist is turned on, otherwise off
$query->get("hostip"); // Host ip
$query->get("hostport"); // Host port
$query->get("players"); // List of online players on the server
```
In case you want to get the raw response, you can use `getStatusRaw()`

## Notes
- Do NOT query a server on the main thread, use an AsyncTask instead
- The info returned by the server can be easily faked, e.g someone doesn't want people to see his highly classified core plugin name, so he removes that info from the query so no one can see it!
