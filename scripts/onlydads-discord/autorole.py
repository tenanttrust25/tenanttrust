#!/usr/bin/env python3
"""Only Dad's Discord — assign the "Open Door" role when a member joins.

Replaces the old 60-second polling script that lived in the deleted
/root/.openclaw/workspace. This version holds a gateway connection, so a role is
assigned the moment someone joins instead of up to a minute later, and Discord
pushes the event rather than us scanning members.

Configuration (environment, see /etc/onlydads-discord.env):
  DISCORD_BOT_TOKEN     required
  DISCORD_GUILD_ID      recommended — restricts everything to one server
  DISCORD_ROLE_NAME     default "Open Door"
  DISCORD_IGNORE_BOTS   default "1"

Usage:
  autorole.py              run the gateway bot (what systemd does)
  autorole.py --discover   show bot identity, guilds and the role it would assign
  autorole.py --backfill [--dry-run]
                           assign the role to existing members who lack it
                           (dry-run by default unless --apply is passed)
"""

from __future__ import annotations

import asyncio
import logging
import os
import sys

import discord


def load_env_file(path: str = "/etc/onlydads-discord.env") -> None:
    """Manual runs (--discover/--backfill) don't get systemd's EnvironmentFile,
    so read it here too. Real environment variables always win."""
    if not os.path.exists(path):
        return
    try:
        with open(path, encoding="utf-8") as handle:
            for line in handle:
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                key, value = line.split("=", 1)
                key = key.strip()
                if key and key not in os.environ:
                    os.environ[key] = value.strip().strip('"').strip("'")
    except OSError as exc:
        logging.getLogger("onlydads.autorole").warning("could not read %s: %s", path, exc)


load_env_file()

TOKEN = os.environ.get("DISCORD_BOT_TOKEN", "").strip()
GUILD_ID_RAW = os.environ.get("DISCORD_GUILD_ID", "").strip()
ROLE_NAME = os.environ.get("DISCORD_ROLE_NAME", "Open Door").strip()
IGNORE_BOTS = os.environ.get("DISCORD_IGNORE_BOTS", "1") == "1"
GUILD_ID = int(GUILD_ID_RAW) if GUILD_ID_RAW.isdigit() else None

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
log = logging.getLogger("onlydads.autorole")

intents = discord.Intents.default()
intents.guilds = True
intents.members = True  # privileged — must be enabled in the Discord developer portal


def find_role(guild: discord.Guild, name: str):
    """Exact match first, then case-insensitive, so 'open door' still works."""
    exact = discord.utils.get(guild.roles, name=name)
    if exact:
        return exact
    lowered = name.lower()
    for role in guild.roles:
        if role.name.lower() == lowered:
            return role
    return None


def wanted_guilds(client: discord.Client):
    if GUILD_ID:
        guild = client.get_guild(GUILD_ID)
        return [guild] if guild else []
    return list(client.guilds)


async def assign(client: discord.Client, member: discord.Member, role: discord.Role, reason: str) -> bool:
    if member.bot and IGNORE_BOTS:
        return False
    if role in member.roles:
        return False
    try:
        await member.add_roles(role, reason=reason)
        log.info("assigned %r to %s (%s)", role.name, member.name, member.id)
        return True
    except discord.Forbidden:
        log.error(
            "cannot assign %r to %s — the bot's role must be ABOVE %r and it needs Manage Roles",
            role.name, member.name, role.name,
        )
    except discord.HTTPException as exc:
        log.error("HTTP error assigning %r to %s: %s", role.name, member.name, exc)
    return False


class AutoRole(discord.Client):
    async def on_ready(self) -> None:
        log.info("connected as %s (%s)", self.user, self.user.id if self.user else "?")
        for guild in wanted_guilds(self):
            role = find_role(guild, ROLE_NAME)
            if role is None:
                log.error(
                    "guild %s (%s): no role named %r — available: %s",
                    guild.name, guild.id, ROLE_NAME,
                    ", ".join(r.name for r in guild.roles if r.name != "@everyone")[:300],
                )
            else:
                log.info("guild %s (%s): will assign %r (%s)", guild.name, guild.id, role.name, role.id)
        if not wanted_guilds(self):
            log.error("bot is not in guild %s — invite it or fix DISCORD_GUILD_ID", GUILD_ID)

    async def on_member_join(self, member: discord.Member) -> None:
        if GUILD_ID and member.guild.id != GUILD_ID:
            return
        role = find_role(member.guild, ROLE_NAME)
        if role is None:
            log.error("join from %s but role %r does not exist", member.name, ROLE_NAME)
            return
        await assign(self, member, role, "Open Door: auto-assigned on join")


async def discover() -> int:
    client = AutoRole(intents=intents)
    try:
        await client.login(TOKEN)
    except discord.LoginFailure:
        print("LOGIN FAILED: the token in DISCORD_BOT_TOKEN is not valid.", file=sys.stderr)
        return 2
    try:
        me = client.user
        print(f"bot: {me} ({getattr(me, 'id', '?')})")
        guilds = [guild async for guild in client.fetch_guilds(limit=50)]
        for guild in guilds:
            print(f"guild: {guild.name}  id={guild.id}")
            full = await client.fetch_guild(guild.id)
            try:
                roles = await full.fetch_roles()
            except discord.Forbidden:
                print("  (cannot read roles — bot needs Manage Roles)")
                continue
            match = [r for r in roles if r.name.lower() == ROLE_NAME.lower()]
            print(f"  role {ROLE_NAME!r}: " + (f"id={match[0].id}" if match else "NOT FOUND"))
            if not match:
                print("  roles: " + ", ".join(r.name for r in roles if r.name != "@everyone"))
        return 0
    finally:
        await client.close()


async def backfill(apply: bool) -> int:
    client = AutoRole(intents=intents)
    # `async with` performs BOTH login and the gateway connect; plain login()
    # leaves wait_until_ready() hanging forever.
    async with client:
        total = 0
        for guild in wanted_guilds(client):
            role = find_role(guild, ROLE_NAME)
            if role is None:
                log.error("guild %s: role %r not found", guild.name, ROLE_NAME)
                continue
            async for member in guild.fetch_members(limit=None):
                if member.bot and IGNORE_BOTS:
                    continue
                if role in member.roles:
                    continue
                total += 1
                if apply:
                    await assign(client, member, role, "Open Door: backfill")
                else:
                    print(f"would assign: {member.name} ({member.id})")
        print(f"{'assigned' if apply else 'would assign'} {total} member(s)")
        return 0


def main() -> int:
    if not TOKEN:
        print("DISCORD_BOT_TOKEN is empty — put the bot token in /etc/onlydads-discord.env", file=sys.stderr)
        return 2

    if "--discover" in sys.argv:
        return asyncio.run(discover())
    if "--backfill" in sys.argv:
        return asyncio.run(backfill(apply="--apply" in sys.argv))

    client = AutoRole(intents=intents)
    try:
        client.run(TOKEN, log_handler=None)
    except discord.PrivilegedIntentsRequired:
        log.error(
            "Discord rejected the connection: enable 'Server Members Intent' for this bot in the "
            "Developer Portal (Applications → Bot → Privileged Gateway Intents)."
        )
        return 3
    return 0


if __name__ == "__main__":
    sys.exit(main())
