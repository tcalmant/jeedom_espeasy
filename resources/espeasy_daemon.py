#!/usr/bin/env python3
"""
ESP Easy daemon
"""

import argparse
import html
import logging
import sys
import urllib.request
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from typing import Dict, List, Optional
from urllib.parse import SplitResult, parse_qs, urlencode, urlsplit, urlunsplit


class DaemonRequestHandler(BaseHTTPRequestHandler):
    """
    Handles a single HTTP request
    """

    jeedom_parsed_url: Optional[SplitResult] = None
    """ URL to Jeedom (setup by run_daemon) """

    def do_GET(self):
        """
        Handle a GET request
        """
        # Parse URL and send data to Jeedom
        split = urlsplit(self.path)
        if split.path == "/favicon.ico":
            # Ignore favicon query
            self.send_response(404)
            self.end_headers()
            return

        content = {k: v[0] for k, v in parse_qs(split.query).items()}
        content["ip"] = self.client_address[0]

        try:
            self.send_to_jeedom(content)
            self.respond_to_esp(True)
        except Exception as ex:
            logging.error("Error sending data to Jeedom: %s", ex)
            self.respond_to_esp(False, str(ex))

    def send_to_jeedom(self, content: Dict[str, str]) -> None:
        """
        Sends the given dictionary to Jeedom
        """
        if self.jeedom_parsed_url is None:
            raise ValueError("Invalid Jeedom URL")

        # Construct the URL
        qs = urlencode(content)
        if self.jeedom_parsed_url.query:
            qs = f"{self.jeedom_parsed_url.query}&{qs}"

        jeedom_url = urlunsplit(
            SplitResult(
                self.jeedom_parsed_url.scheme,
                self.jeedom_parsed_url.netloc,
                self.jeedom_parsed_url.path,
                qs,
                self.jeedom_parsed_url.fragment,
            )
        )

        logging.debug("Sending data from %s to %s", self.client_address[0], jeedom_url)

        # Send the query
        req = urllib.request.Request(jeedom_url, method="PUT")
        with urllib.request.urlopen(req) as response:
            raw_response = response.read()

            if response.status == 200:
                logging.debug("Data sent successfully")
            else:
                message = (
                    "Error sending data to Jeedom. "
                    f"Code={response.status}, Reason={response.reason}"
                )
                logging.error(message)
                logging.info("Error content:\n%s", raw_response)
                raise IOError(message)

    def respond_to_esp(self, success: bool, reason: Optional[str] = None) -> None:
        """
        Sends a response to the client
        """
        response = f"""<html>
<head>
<title>ESP Easy daemon for Jeedom</title>
</head>
<body>"""
        if success:
            response = f"""{response}
<p>Jeedom received data from <code>{self.client_address[0]}</code></p>
"""
        else:
            response = f"""{response}
<p>Error sending data from <code>{self.client_address[0]}</code> to Jeedom</p>
<pre>
{html.escape(reason) or "n/a"}
</pre>
"""

        response = f"""{response}
</body>
</html>
"""

        content = response.encode("utf-8")
        self.send_response(200)
        self.send_header("content-type", "text/html; charset=UTF-8")
        self.send_header("content-length", len(content))
        self.end_headers()
        self.wfile.write(content)
        self.wfile.flush()

    def log_request(self, *args, **kwargs) -> None:
        # Ignore successful requests logging
        return None

def run_daemon(server_host: str, server_port: int, jeedom_url: str) -> int:
    """
    Runs the daemon server (blocking)
    """
    # Setup the request handler with the Jeedom URL
    DaemonRequestHandler.jeedom_parsed_url = urlsplit(jeedom_url)

    # Start the server
    server = ThreadingHTTPServer(
        (server_host, server_port), DaemonRequestHandler, False,
    )
    server.server_bind()
    addr, port = server.socket.getsockname()
    logging.info("Server is waiting on address %s, port %d", addr, port)

    try:
        server.server_activate()
        server.serve_forever()
    except KeyboardInterrupt:
        logging.info("Daemon interrupted")
        server.shutdown()
        return 127


def main(args: Optional[List[str]] = None) -> int:
    """
    Script entry point
    """
    log_levels = list(logging._levelToName.values())

    parser = argparse.ArgumentParser()
    parser.add_argument(
        "-l", "--level", choices=log_levels, default="INFO", help="Log level"
    )
    group = parser.add_argument_group("Daemon server")
    group.add_argument("-a", "--address", default="0.0.0.0", help="Binding address")
    group.add_argument("-p", "--port", type=int, default=8121, help="Binding port")
    group = parser.add_argument_group("Jeedom")
    group.add_argument("--jeedom", required=True, help="URL to Jeedom API")
    options = parser.parse_args(args)

    logging.basicConfig(
        level=logging.getLevelName(options.level),
        format="%(asctime)s :: %(levelname)s :: %(message)s",
    )

    return run_daemon(options.address, options.port, options.jeedom)


if __name__ == "__main__":
    sys.exit(main())
