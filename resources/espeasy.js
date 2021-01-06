/**
 * Node.js daemon for the ESP-easy plugin
 */

const http = require("http");
const request = require("request");

/**
 * Local IP address and port to bind
 */
const serverIP = process.argv[2];
const serverPort = 8121;

/**
 * URL to access the Jeedom-side PHP page of the plugin (contains the API key)
 */
const urlJeedom = new URL(process.argv[3]);

/**
 * Log level (none by default)
 */
const loglevel = process.argv[4] || 1000; // Default no log at all

/**
 * Ignore TLS certificates
 */
process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

/**
 * Generates the HTML page returned to the client
 *
 * @param {String} message Message to display
 */
function makeResponsePage(message) {
  return `<html>
<head>
<title>Jeedom - ESP Easy</title>
</head>
<body>
<h1>Jeedom - ESP Easy</h1>
<p>`
    + message
    + `</p>
</body>
</html>
`;
}

/**
 * Sends a response page to the client
 *
 * @param {http.ServerResponse} res Object to answer the client
 * @param {String} message Message in the returned HTML page
 * @param {int} code HTTP status code
 */
function sendResponse(res, message, code=200) {
  res.writeHead(code, { "Content-Type": "text/html" });

  // Write out the HTTP response body
  res.write(makeResponsePage(message));

  // End of HTTP response
  res.end();
}

/**
 * Client request handling (from ESP)
 *
 * Propagates the message to Jeedom
 *
 * @param {http.IncomingMessage} req Details about the request
 * @param {http.ServerResponse} res Object to answer to the client
 */
function answer(req, res) {
  if(req.method != "GET") {
    // HTTP response header - the content will be HTML MIME type
    sendResponse(res, "Unhandled HTTP method. Use GET.", 405);
    return false;
  }

  // Parse the URL the client requested
  var parsedUrl = new URL(req.url, "http://" + serverIP + ":" + serverPort);

  if(parsedUrl.search == "") {
    // No parameter given: nothing we can do with it
    sendResponse(res, "Nothing to do without a query string", 200);
    return false;
  }

  // Compute the IP address of the ESP: either use the one given by the ESP in
  // the URL or the socket client IP.
  if(!parsedUrl.searchParams.has("ip")) {
    parsedUrl.searchParams.set("ip", req.socket.remoteAddress);
  }

  if (loglevel <= 200) {
    // INFO
    console.log("Got query from " + parsedUrl.searchParams.get("ip") + ":" + parsedUrl.search);
  }

  // Prepare the URL to notify Jeedom
  var updateURL = new URL("", urlJeedom);
  for (const [key, value] of parsedUrl.searchParams) {
    updateURL.searchParams.set(key, value);
  }

  if (loglevel <= 100) {
    // DEBUG
    console.log("Calling Jeedom " + updateURL);
  }

  // Send the query
  request({
    url: updateURL,
    method: 'PUT',
  },
    function (error, response, body) {
      if (error || response.statusCode != 200) {
        var statusCode = "";
        if(response != undefined) {
          statusCode = "(HTTP " + response.statusCode + ")";
        }
        console.error((
          new Date())
          + " - Error calling Jeedom side: "
          + error
          + " "
          + statusCode
        );
      }
    });

  // Send a valid response to the client
  sendResponse(res, "Data received");
  return true;
}

/************************/
/*  START THE SERVER    */
/************************/

// Create the HTTP server
var server = http.createServer(answer);

// Turn server on - now listening for requests on localIP and port
server.listen(serverPort, serverIP);

// Print message to terminal that server is running
console.log("Server running");
