FROM hexpm/elixir:1.16.3-erlang-26.2.5-alpine-3.19.1

RUN apk add --no-cache build-base git

WORKDIR /app

# Install Hex + Rebar
RUN mix local.hex --force && mix local.rebar --force

# Copy mix files first for better layer caching
COPY mix.exs mix.lock ./
COPY config ./config

RUN mix deps.get

# Copy the rest of the app
COPY . .

EXPOSE 4000

CMD ["sh", "-lc", "mix deps.get && mix ecto.create && mix ecto.migrate && mix phx.server"]
