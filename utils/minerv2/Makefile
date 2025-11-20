# Compiler and Flags
CC = gcc
CFLAGS = -Wall -O3 -pthread
LDFLAGS = -static
LIBS = -lgmp -lcurl -largon2 -lcrypto -lpthread

# Source and Object Files
SRCS_BENCH = src/miner_core.c src/benchmark.c
OBJS_BENCH = $(SRCS_BENCH:.c=.o)

SRCS_MINER = src/miner_core.c src/c_miner.c
OBJS_MINER = $(SRCS_MINER:.c=.o)

# Executables
TARGET_BENCH = benchmark
TARGET_MINER = c_miner

.PHONY: all clean

all: $(TARGET_BENCH) $(TARGET_MINER)

# --- Build Rules ---

$(TARGET_BENCH): $(OBJS_BENCH)
	$(CC) $(CFLAGS) $(LDFLAGS) -o $@ $^ $(LIBS)

$(TARGET_MINER): $(OBJS_MINER)
	$(CC) $(CFLAGS) $(LDFLAGS) -o $@ $^ $(LIBS)

# Generic rule for object files
%.o: %.c
	$(CC) $(CFLAGS) -c -o $@ $<

# --- Housekeeping ---

clean:
	rm -f src/*.o $(TARGET_BENCH) $(TARGET_MINER)

# --- PHONY targets for convenience ---
run-bench: all
	@./$(TARGET_BENCH)

run-miner: all
	@echo "Usage: ./c_miner -n <node> -a <address> -t <threads>"

.DEFAULT_GOAL := all
