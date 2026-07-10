# Miner Speedup

This document explains the changes made to speed up the nodeminer.

## Problem

The nodeminer was slow to start working on a new block because it only checked for a new block periodically. This meant that the miner could be wasting time trying to mine a block that was already found by another miner.

## Solution

The solution was to make the miner check for a new block after every hash. This was made possible by implementing an in-memory cache for the mining information.

### In-Memory Cache

An in-memory cache was implemented using a static variable in the `Blockchain` class. This makes getting the mining information extremely fast, as it does not require any database or filesystem access.

The `Blockchain::getMineInfo()` method was refactored to use this in-memory cache. A new method, `Blockchain::invalidateMineInfo()`, was added to clear the cache.

### Cache Invalidation

The cache is invalidated whenever the blockchain changes. This is done by calling `Blockchain::invalidateMineInfo()` in the `Block::add()` and `Block::delete()` methods.

### Miner Update

The `NodeMiner` was updated to call `Blockchain::getMineInfo()` after every hash. This allows the miner to quickly detect when a new block has been found and to start working on the next block immediately.
