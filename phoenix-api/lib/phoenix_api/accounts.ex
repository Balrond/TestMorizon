defmodule PhoenixApi.Accounts do
  import Ecto.Query, warn: false

  alias PhoenixApi.Repo
  alias PhoenixApi.Accounts.User
  alias PhoenixApi.NamesCsv

  @default_count 100
  @birth_from ~D[1970-01-01]
  @birth_to ~D[2024-12-31]
  @csv_dir Path.join([:phoenix_api |> :code.priv_dir() |> to_string(), "names"])

  @genders [:male, :female]

  @allowed_sort_fields ~w(
    first_name
    last_name
    gender
    birthdate
    inserted_at
    updated_at
    external_id
  )a

  # --- IMPORT ---

  def import_from_csv_sources(count \\ @default_count) when is_integer(count) and count > 0 do
    names = load_names(csv_limit())
    now = NaiveDateTime.utc_now() |> NaiveDateTime.truncate(:second)

    users =
      generate_users(count, names)
      |> Enum.map(&Map.merge(&1, %{inserted_at: now, updated_at: now}))

    {inserted, _} =
      Repo.insert_all(User, users,
        on_conflict: :nothing,
        conflict_target: [:first_name, :last_name, :birthdate, :gender]
      )

    {:ok, %{requested: count, inserted: inserted, skipped: count - inserted}}
  end

  def preview_import_from_csv_sources(count \\ @default_count)
      when is_integer(count) and count > 0 do
    names = load_names(csv_limit())
    generate_users(count, names)
  end

  # --- CRUD (used by controllers/tests) ---

  # Supports:
  # Filters:
  # - first_name, last_name (substring, case-insensitive)
  # - gender (male|female)
  # - birthdate_from, birthdate_to (YYYY-MM-DD)
  # Sorting:
  # - sort (allowed column), dir (asc|desc)
  def list_users(params \\ %{}) when is_map(params) do
    User
    |> apply_filters(params)
    |> apply_sort(params)
    |> Repo.all()
  end

  # IMPORTANT: non-bang version for clean 404 handling in controllers
  def get_user(id), do: Repo.get(User, id)

  def create_user(attrs \\ %{}) do
    %User{}
    |> User.changeset(attrs)
    |> Repo.insert()
  end

  def update_user(%User{} = user, attrs) do
    user
    |> User.changeset(attrs)
    |> Repo.update()
  end

  def delete_user(%User{} = user), do: Repo.delete(user)

  def change_user(%User{} = user, attrs \\ %{}), do: User.changeset(user, attrs)

  # --- PRIVATE ---

  defp apply_filters(query, params) do
    query
    |> maybe_ilike(:first_name, Map.get(params, "first_name"))
    |> maybe_ilike(:last_name, Map.get(params, "last_name"))
    |> maybe_gender(Map.get(params, "gender"))
    |> maybe_birth_from(Map.get(params, "birthdate_from"))
    |> maybe_birth_to(Map.get(params, "birthdate_to"))
  end

  defp maybe_ilike(query, _field, nil), do: query
  defp maybe_ilike(query, _field, ""), do: query

  defp maybe_ilike(query, field, value) when is_binary(value) do
    value = String.trim(value)

    if value == "" do
      query
    else
      where(query, [u], ilike(field(u, ^field), ^"%#{value}%"))
    end
  end

  defp maybe_gender(query, nil), do: query
  defp maybe_gender(query, ""), do: query

  defp maybe_gender(query, gender) when gender in ["male", "female"],
    do: where(query, [u], u.gender == ^gender)

  defp maybe_gender(query, _), do: query

  defp maybe_birth_from(query, nil), do: query
  defp maybe_birth_from(query, ""), do: query

  defp maybe_birth_from(query, v) when is_binary(v) do
    case Date.from_iso8601(String.trim(v)) do
      {:ok, date} -> where(query, [u], u.birthdate >= ^date)
      _ -> query
    end
  end

  defp maybe_birth_to(query, nil), do: query
  defp maybe_birth_to(query, ""), do: query

  defp maybe_birth_to(query, v) when is_binary(v) do
    case Date.from_iso8601(String.trim(v)) do
      {:ok, date} -> where(query, [u], u.birthdate <= ^date)
      _ -> query
    end
  end

  defp apply_sort(query, params) do
    sort = Map.get(params, "sort")
    dir = Map.get(params, "dir")

    field =
      if sort in @allowed_sort_fields do
        String.to_atom(sort)
      else
        :inserted_at
      end

    direction =
      case dir do
        "desc" -> :desc
        "DESC" -> :desc
        _ -> :asc
      end

    order_by(query, [u], [{^direction, field(u, ^field)}])
  end

  # CSV_NAMES_LIMIT:
  # - unset/empty/invalid => :infinity (no limit)
  # - positive integer => that limit
  defp csv_limit do
    with v when is_binary(v) <- System.get_env("CSV_NAMES_LIMIT"),
         v when v != "" <- String.trim(v),
         {n, ""} <- Integer.parse(v),
         true <- n > 0 do
      n
    else
      _ -> :infinity
    end
  end

  defp load_names(limit) do
    %{
      female_first:
        NamesCsv.top_first_column(Path.join(@csv_dir, "first_names_female.csv"), limit),
      male_first: NamesCsv.top_first_column(Path.join(@csv_dir, "first_names_male.csv"), limit),
      female_last: NamesCsv.top_first_column(Path.join(@csv_dir, "last_names_female.csv"), limit),
      male_last: NamesCsv.top_first_column(Path.join(@csv_dir, "last_names_male.csv"), limit)
    }
  end

  defp generate_users(count, names) when is_integer(count) and count > 0 do
    for _ <- 1..count do
      gender = Enum.random(@genders)

      {first, last} =
        case gender do
          :female -> {Enum.random(names.female_first), Enum.random(names.female_last)}
          :male -> {Enum.random(names.male_first), Enum.random(names.male_last)}
        end

      %{
        external_id: Ecto.UUID.generate(),
        first_name: normalize_name(first),
        last_name: normalize_name(last),
        gender: Atom.to_string(gender),
        birthdate: random_birthdate(@birth_from, @birth_to)
      }
    end
  end

  defp normalize_name(v) when is_binary(v) do
    v
    |> String.trim()
    |> String.capitalize()
  end

  defp random_birthdate(from, to) do
    from_days = from |> Date.to_erl() |> :calendar.date_to_gregorian_days()
    to_days = to |> Date.to_erl() |> :calendar.date_to_gregorian_days()

    Enum.random(from_days..to_days)
    |> :calendar.gregorian_days_to_date()
    |> Date.from_erl!()
  end

  def get_user!(id), do: Repo.get!(User, id)
end
