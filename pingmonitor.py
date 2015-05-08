import random
import select
import socket
import sys
import time
import os
import errno
from datetime import datetime
 
def make_sure_path_exists(path):
    try:
        os.makedirs(path)
    except OSError as exception:
        if exception.errno != errno.EEXIST:
            raise

def chk(data):
    x = sum(a + b * 256 for a, b in zip(data[::2], data[1::2] + b'\x00')) & 0xFFFFFFFF
    x = (x >> 16) + (x & 0xFFFF)
    x = (x >> 16) + (x & 0xFFFF)
    return (~x & 0xFFFF).to_bytes(2, 'little')
 
 
def ping(addr, timeout=1):
    with socket.socket(socket.AF_INET, socket.SOCK_RAW, socket.IPPROTO_ICMP) as conn:
        payload = random.randrange(0, 65536).to_bytes(2, 'big') + b'\x01\x00'
        packet  = b'\x08\x00' + b'\x00\x00' + payload
        packet  = b'\x08\x00' + chk(packet) + payload
        try:
            conn.connect((addr, 80))
        except Exception:
            return None
            
        try:
            conn.sendall(packet)
        except Exception:
            return None
     
        start = time.time()
     
        while select.select([conn], [], [], max(0, start + timeout - time.time()))[0]:
            packet    = conn.recv(1024)[20:]
            unchecked = packet[:2] + b'\0\0' + packet[4:]
     
            if packet == b'\0\0' + chk(unchecked) + payload:
                return int((time.time() - start) * 1000)


pingTargets = ("google.com", "192.168.1.1")
pingInterval = 5 # how often to ping targets, in seconds
fileUpdateInterval = 60 # how often to write ping data from memory to file, in seconds

# create necessary directories if they don't exist
for pingTarget in pingTargets:
    make_sure_path_exists("pings/" + pingTarget)

print("begin pinging")
while True:
    now = datetime.now()
    midnight = now.replace(hour=0, minute=0, second=0, microsecond=0)
    daySeconds = (now - midnight).seconds
    pings = {}
    fileHandles = {}
    for pingTarget in pingTargets:
        fileHandles[pingTarget] = open("pings/" + pingTarget + "/" + time.strftime("%Y-%m-%d"), 'ab')
        pings[pingTarget] = []

    while (datetime.now() - now).seconds < fileUpdateInterval:
        for pingTarget in pingTargets:
            currentPing = ping(pingTarget)
            if currentPing is None:
                currentPing = 0
            pings[pingTarget].append((daySeconds.to_bytes(4, sys.byteorder), currentPing.to_bytes(4, sys.byteorder)))

        time.sleep(pingInterval)
        daySeconds = daySeconds + pingInterval
    # write pairs of time-ping to file (4 byte integer, 4 byte integer)
    for pingTarget in pingTargets:
        f = fileHandles[pingTarget]
        for p in pings[pingTarget]:
            f.write(p[0])
            f.write(p[1])
        f.close()