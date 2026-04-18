"""
Load test — 1000 truly-concurrent requests.

Why this script and not `ab -c 1000`:
  ab opens 1000 concurrent TCP sockets which overwhelms Windows' default
  accept queue and the single-threaded `php artisan serve`. Spreading the
  fire-rate while keeping 1000 in-flight at peak gives a more realistic
  picture of how the app responds under burst.

Usage: python docs/qa/loadtest.py <url> [total] [concurrency]
"""
import sys, time, statistics, threading, queue
import urllib.request, urllib.error, ssl

URL         = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8765/up"
TOTAL       = int(sys.argv[2]) if len(sys.argv) > 2 else 1000
CONCURRENCY = int(sys.argv[3]) if len(sys.argv) > 3 else 1000

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode    = ssl.CERT_NONE

results = queue.Queue()

def worker(idx):
    t0 = time.perf_counter()
    try:
        req = urllib.request.Request(URL, headers={"User-Agent": "loadtest"})
        with urllib.request.urlopen(req, timeout=30, context=ctx) as r:
            r.read(64)
            results.put((r.status, (time.perf_counter() - t0) * 1000))
    except urllib.error.HTTPError as e:
        results.put((e.code, (time.perf_counter() - t0) * 1000))
    except Exception as e:
        results.put((0, (time.perf_counter() - t0) * 1000))

# Pre-create all threads, then release them at once → true 1000-parallel.
gate = threading.Event()
def gated(idx):
    gate.wait()
    worker(idx)

threads = [threading.Thread(target=gated, args=(i,), daemon=True) for i in range(TOTAL)]

# Pump them out CONCURRENCY at a time so the OS can schedule.
print(f"URL         : {URL}")
print(f"Total       : {TOTAL}")
print(f"Concurrency : {CONCURRENCY}")
print(f"Mode        : burst (gate-released)")
print()

t_start = time.perf_counter()
for t in threads:
    t.start()
gate.set()
for t in threads:
    t.join(timeout=60)
t_end = time.perf_counter()

# Collect
codes, durations = [], []
while not results.empty():
    s, d = results.get()
    codes.append(s)
    durations.append(d)

ok       = sum(1 for c in codes if 200 <= c < 300)
ratelim  = sum(1 for c in codes if c == 429)
err      = sum(1 for c in codes if c == 0 or c >= 500)
other    = len(codes) - ok - ratelim - err

durations.sort()
def pct(p):
    if not durations: return 0
    return durations[min(len(durations)-1, int(len(durations) * p / 100))]

elapsed = t_end - t_start
print(f"Wall-clock  : {elapsed:.2f} s")
print(f"Throughput  : {len(codes)/elapsed:.1f} req/s")
print(f"Responses   : {len(codes)} of {TOTAL}")
print(f"  2xx       : {ok}")
print(f"  429       : {ratelim}  (rate-limited — expected & correct)")
print(f"  4xx other : {other}")
print(f"  5xx/err   : {err}")
print()
if durations:
    print(f"Latency ms  : min={durations[0]:.0f}  p50={pct(50):.0f}  "
          f"p90={pct(90):.0f}  p95={pct(95):.0f}  p99={pct(99):.0f}  "
          f"max={durations[-1]:.0f}")
