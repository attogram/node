# Getting Started

## What is PHP Coin

PHP Coin is a new unique cryptocurrency in the blockchain world which aims to be simple and easy to understand.

### Basic principles

PHP Coin blockchain using cryptography as its core model.

Main basic block of PHP Coin
is an **account**.

It is set consisting of private key, public key and [address](Creating-address.md)
which are linked and unique.

_private key -> public key -> address_

A **private key** is like a password, it must be stored, secured and never shared.

**Public key** is derived from private key and is used to verify actions invoked by address.

**Address** is the main identifier of the account.

Address and public key are free to share.

**Transaction** is action that is invoked by some action from one address.

It is signed with the sender's private key and then verified by the sender public key on the other side.

More transactions executed in some period form a **block** that moves blockchain forward.

### Generating address

You can generate PHP Coin address in many ways.

No matter in which way the generated account is unique and irreversible.

When address is generated it is only in user possession until it is not used on blockchain.

For many features on blockchain address need to be verified

See: [Creating-address](Creating-address)

See: [Verifying-address](Verifying-address)