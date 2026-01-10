defmodule PhoenixApi.NamesCsv do
  @moduledoc false

  @default_limit 100
  @header_markers ~w(imi nazw name surname)

  @spec top_first_column(Path.t(), pos_integer() | :infinity) :: [String.t()]
  def top_first_column(path, limit \\ @default_limit) do
    path
    |> File.stream!()
    |> Stream.map(&String.trim/1)
    |> Stream.reject(&(&1 == ""))
    |> drop_header()
    |> Stream.map(&first_cell/1)
    |> Stream.map(&cleanup/1)
    |> Stream.reject(&(&1 == ""))
    |> take(limit)
  end

  # PRIVATE

  defp take(stream, :infinity), do: Enum.to_list(stream)
  defp take(stream, limit) when is_integer(limit) and limit > 0, do: Enum.take(stream, limit)

  defp drop_header(stream) do
    case Enum.take(stream, 1) do
      [first] ->
        down = String.downcase(first)

        if Enum.any?(@header_markers, &String.contains?(down, &1)) do
          Stream.drop(stream, 1)
        else
          stream
        end

      [] ->
        stream
    end
  end

  defp first_cell(line) do
    cond do
      String.contains?(line, ";") -> line |> String.split(";", parts: 2) |> hd()
      String.contains?(line, ",") -> line |> String.split(",", parts: 2) |> hd()
      true -> line
    end
  end

  defp cleanup(value) do
    value
    |> String.trim()
    |> String.trim("\"")
  end
end
