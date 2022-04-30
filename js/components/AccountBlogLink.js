import Twitter from "./icons/Twitter";
import Tumblr from "./icons/Tumblr";
import apiFetch from "@wordpress/api-fetch";

const { useEffect, useState } = wp.element;

export const AccountBlogLink = (props) => {
  const [isBusy, setIsBusy] = useState(false);
  const [canPush, setCanPush] = useState(false);
  const [canPull, setCanPull] = useState(false);

  const {
    account: {
      id,
      user_id: ownerId,
      social_type: type = "",
      social_username: name = "",
      additional_info: info = "",
      can_push: push = false,
      can_pull: pull = false,
    },
    currentUserId,
  } = props;

  useEffect(() => {
    setCanPush(push);
    setCanPull(pull);
  }, [push, pull]);

  const getIcon = () => {
    switch (type) {
      case "twitter":
        return <Twitter />;
      case "tumblr":
        return <Tumblr />;
    }
    return <span />;
  };

  const disabled = () => isBusy || currentUserId != ownerId;

  const onClick = () => {
    setIsBusy(true);
    setPermissions().then(() => setIsBusy(false));
  };

  const setPermissions = async () => {
    const result = await apiFetch({
      path: "/smolblog/v1/accounts/blogs/setpermissions",
      method: "POST",
      data: {
        social_id: id,
        push: canPush,
        pull: canPull,
      },
    });

    if (!result.success) {
      throw Error(`Error from Smolblog: ${JSON.stringify(result)}`);
    }
  };

  return (
    <tr className={type}>
      <td>{getIcon()}</td>
      <td>
        {name} <span className="additional-info">{info}</span>
      </td>
      <td>
        <input
          type="checkbox"
          name={`checkbox-${id}-push`}
          id={`checkbox-${id}-push`}
          checked={canPush}
          disabled={disabled()}
          onChange={() => setCanPush(!canPush)}
        />
      </td>
      <td>
        <input
          type="checkbox"
          name={`checkbox-${id}-pull`}
          id={`checkbox-${id}-pull`}
          checked={canPull}
          disabled={disabled()}
          onChange={() => setCanPull(!canPull)}
        />
      </td>
      <td>
        <button disabled={disabled()} onClick={onClick}>
          Save
        </button>
      </td>
    </tr>
  );
};
