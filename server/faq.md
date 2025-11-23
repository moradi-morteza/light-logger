### Why do client applications send logs over HTTP/2 instead of WebSocket?

HTTP/2 is a better fit for log delivery because log messages are **stateless**, **small**, and **one-way**. WebSocket is designed for **persistent**, **real-time**, **two-way** communication, which adds unnecessary overhead for simple log events.
1. **Log messages don’t need a persistent connection**
2. 
   WebSocket requires keeping thousands of connections open, while logs are usually one-shot events.

2. **HTTP/2 works perfectly behind load balancers, proxies, and Kubernetes**

   WebSocket often needs sticky sessions, special configs, and suffers from idle timeouts.

3. **Lower overhead**

   HTTP/2 multiplexing makes POST requests extremely lightweight for small payloads.

4. **Easier for all client environments**

   CLI scripts, cron jobs, serverless functions, and simple services can’t maintain WebSocket connections, but HTTP/2 works everywhere.

5. **WebSocket is reserved for the dashboard / real-time viewer**

   The server keeps WebSocket only for streaming logs live to the UI, not for collecting them.
