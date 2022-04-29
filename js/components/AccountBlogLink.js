import Twitter from "./icons/Twitter";
import Tumblr from "./icons/Tumblr";

const { useState } = wp.element;

export const AccountBlogLink = (props) => {
  const [isBusy, setIsBusy] = useState(false);

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

  const getIcon = () => {
    switch (type) {
      case "twitter":
        return <Twitter />;
      case "tumblr":
        return <Tumblr />;
    }
    return <span />;
  };

  const getCheckbox = (name, check) => {
    return (
      <input
        type="checkbox"
        name={name}
        id={`checkbox-${id}-${name}`}
        checked={check}
        disabled={disabled()}
      />
    );
  };

  const disabled = () => isBusy || currentUserId != ownerId;

  return (
    <tr>
      <td className={type}>
        {getIcon()} {name}
        <span className="additional-info">{info}</span>
      </td>
      <td>{getCheckbox("push", push)}</td>
      <td>{getCheckbox("pull", pull)}</td>
      <td>
        <button disabled={disabled()}>Save</button>
      </td>
    </tr>
  );
};

const yesOrNo = (check) => {
  return check && check > 0 ? (
    <span style={{ color: "green", "font-weight": "bold" }}>Yes</span>
  ) : (
    <span style={{ color: "red", "font-weight": "bold" }}>No</span>
  );
};
