var http = require('http'),
  url = require("url"),
  request = require('request'),
  ipaddr = process.argv[2],
  urlJeedom = process.argv[3],
  loglevel = process.argv[4] || 1000; // Default no log at all

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

function answer(req, res) {
  var ipString = req.connection.remoteAddress;
  var decodeUrl = decodeURIComponent(req.url).replace(/[/]/g, "");
  if (loglevel <= 200) {
    // INFO
    console.log("We have got a request for " + decodeUrl + " from " + ipString);
  }

  var urlj = urlJeedom + "&ip=" + ipString + "&" + decodeUrl;
  if (loglevel <= 100) {
    // DEBUG
    console.log("Calling Jeedom " + urlj);
  }
  request({
    url: urlj,
    method: 'PUT',
  },
    function (error, response, body) {
      if (!error && response.statusCode == 200) {
        if (loglevel <= 100) {
          // DEBUG
          console.log((new Date()) + "Got response Value: " + response.statusCode);
        }
      } else {
        console.log((new Date()) + " - Error: " + response.statusCode + " - " + error);
      }
    });

  // HTTP response header - the content will be HTML MIME type
  res.writeHead(200, { 'Content-Type': 'text/html' });

  // Write out the HTTP response body
  res.write('<html><body>' +
    '<h1>Jeedom receive<h1>' +
    '</body></html>');

  // End of HTTP response
  res.end();
}

/************************/
/*  START THE SERVER    */
/************************/

// Create the HTTP server
var server = http.createServer(answer);

// Turn server on - now listening for requests on localIP and port
server.listen(8121, ipaddr);

// print message to terminal that server is running
console.log('Server running');
