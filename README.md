# 🚀 NitroJSON

Fast & Memory-Efficient JSON field extractor for PHP 8.4+ powered by Rust SIMD.

## 💡 The Problem
Standard `json_decode()` in PHP is memory-hungry. When parsing a 500MB JSON, PHP often hits the `memory_limit` because it builds a massive array tree in the heap.

## ⚡ The Solution
NitroJSON uses **Rust**, **SIMD instructions**, and **Memory Mapping (mmap)** to extract specific fields without loading the whole file into PHP's memory.

### 📊 Performance Benchmark (500MB JSON)
| Metric          | `json_decode()` | NitroJSON      |
|:----------------|:---------------:|:--------------:|
| **Time** | 0.8s            | **0.5s** |
| **PHP Memory** | **~1.5 GB** | **~0 MB** |

## 🛠 Installation

1. Clone this repo.
2. Build the Rust library:
   ```bash
   cargo build --release
3. include php/NitroJson.php in your project.

## Usage

```php
require_once 'php/NitroJson.php';
NitroJson::load(); 

$val = nitro_json_from_file('huge_log.json', 'data.user.email');
echo $val;
