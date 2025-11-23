

<div align="center">
  <img src="logo.png" alt="Light Logger Logo" width="100"/> 

# Light Logger

A high-performance, distributed logging system built with PHP Swoole. Designed for microservices architectures with real-time log streaming, centralized storage, and dynamic log control.

</div>


<p align="center">
  <img src="architecture.jpg" alt="Light Logger Architecture" width="700">
</p>

> **Note:** This project is under active development. Features and APIs may change.

## Features

- **High Performance** - Built on PHP Swoole with coroutine support for non-blocking I/O
- **HTTP/2 Support** - Efficient log ingestion from application pods via HTTP/2 connection pooling
- **Real-time Streaming** - WebSocket-based live log viewing for dashboards
- **Dynamic Log Control** - Enable/disable logging for specific users in real-time via Redis pub/sub
- **Kubernetes Ready** - Health check endpoints and scalable architecture

## Architecture

Light Logger consists of the following components:

| Component | Description |
|-----------|-------------|
| **Logger Server** | PHP Swoole-based HTTP & WebSocket server (core) |
| **Logger Panel** | Vue.js dashboard for querying and live log viewing |
| **Elasticsearch** | Log storage and search engine |
| **Redis** | Command distribution to application pods |

### Data Flow

1. **Log Ingestion**: Application pods send logs via HTTP/2 to Logger Server
2. **Querying**: Panel queries logs via HTTP from Logger Server
3. **Live Streaming**: Panel receives real-time logs via WebSocket
4. **Commands**: Dynamic commands (e.g., enable user logging) flow through Redis to pods

## Requirements

- PHP 8.3+
- Swoole extension 6.0+
- Redis 7.x
- Node.js 18+ (for panel)

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) before submitting a Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Author

**Morteza Moradi** - [moradiemails@gmail.com](mailto:moradiemails@gmail.com)

## Acknowledgments

- [Swoole](https://www.swoole.com/) - High-performance coroutine-based PHP framework
- [Vue.js](https://vuejs.org/) - Progressive JavaScript framework
